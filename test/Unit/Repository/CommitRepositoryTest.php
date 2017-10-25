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

namespace Localheinz\GitHub\ChangeLog\Test\Unit\Repository;

use Github\Api;
use Localheinz\GitHub\ChangeLog\Exception;
use Localheinz\GitHub\ChangeLog\Repository;
use Localheinz\GitHub\ChangeLog\Resource;
use Localheinz\Test\Util\Helper;
use PHPUnit\Framework;

final class CommitRepositoryTest extends Framework\TestCase
{
    use Helper;

    public function testImplementsCommitRepositoryInterface()
    {
        $this->assertClassImplementsInterface(Repository\CommitRepositoryInterface::class, Repository\CommitRepository::class);
    }

    public function testShowReturnsCommitEntityWithShaAndMessageOnSuccess()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $expectedItem = $this->commitItem();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($sha)
            )
            ->willReturn($expectedItem);

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commit = $commitRepository->show(
            $repository,
            $sha
        );

        $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

        $this->assertSame($expectedItem['sha'], $commit->sha());
        $this->assertSame($expectedItem['commit']['message'], $commit->message());
    }

    public function testShowThrowsCommitNotFoundOnFailure()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $api = $this->createCommitApiMock();

        $api
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($sha)
            )
            ->willReturn('failure');

        $commitRepository = new Repository\CommitRepository($api);

        $this->expectException(Exception\ReferenceNotFound::class);

        $commitRepository->show(
            $repository,
            $sha
        );
    }

    public function testAllReturnsEmptyArrayOnFailure()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn('snafu');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all($repository, [
            'sha' => $sha,
        ]);

        $this->assertInstanceOf(Resource\Range::class, $range);
        $this->assertCount(0, $range->commits());
    }

    public function testAllSetsParamsPerPageTo250()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('per_page', 250)
            )
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all($repository, [
            'sha' => $sha,
        ]);
    }

    public function testAllStillAllowsSettingPerPage()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;
        $perPage = $faker->numberBetween(1);

        $commitApi = $this->createCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('per_page', $perPage)
            )
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->all($repository, [
            'sha' => $sha,
            'per_page' => $perPage,
        ]);
    }

    public function testAllReturnsRange()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $sha = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $expectedItems = $this->commitItems(15);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $sha)
            )
            ->willReturn($this->reverse($expectedItems));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->all($repository, [
            'sha' => $sha,
        ]);

        $this->assertInstanceOf(Resource\Range::class, $range);

        $commits = $range->commits();

        $this->assertCount(\count($expectedItems), $commits);

        \array_walk($commits, function (Resource\CommitInterface $commit) use (&$expectedItems) {
            /*
             * API returns commits in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            $this->assertSame($expectedItem['sha'], $commit->sha());
            $this->assertSame($expectedItem['commit']['message'], $commit->message());
        });
    }

    public function testItemsDoesNotFetchCommitsIfStartAndEndReferencesAreTheSame()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $endReference = $startReference;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->never())
            ->method($this->anything());

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);
        $this->assertEmpty($range->commits());
        $this->assertEmpty($range->pullRequests());
    }

    public function testItemsDoesNotFetchCommitsIfStartCommitCouldNotBeFound()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($startReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects($this->never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);
        $this->assertEmpty($range->commits());
        $this->assertEmpty($range->pullRequests());
    }

    public function testItemsDoesNotFetchCommitsIfEndCommitCouldNotBeFound()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($startReference)
            )
            ->willReturn($this->commitItem());

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($endReference)
            )
            ->willReturn(null);

        $commitApi
            ->expects($this->never())
            ->method('all');

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);
        $this->assertEmpty($range->commits());
        $this->assertEmpty($range->pullRequests());
    }

    public function testItemsFetchesCommitsUsingShaFromEndCommit()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem();

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $endCommit = $this->commitItem();

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($endReference)
            )
            ->willReturn($endCommit);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $endCommit['sha'])
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );
    }

    public function testItemsFetchesCommitsIfEndReferenceIsNotGiven()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem();

        $commitApi
            ->expects($this->once())
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayNotHasKey('sha')
            );

        $commitRepository = new Repository\CommitRepository($commitApi);

        $commitRepository->items(
            $repository,
            $startReference
        );
    }

    public function testItemsReturnsRangeOfCommitsFromEndToStartExcludingStart()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $endCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($endReference)
            )
            ->willReturn($endCommit);

        $countBetween = 9;
        $countBefore = 2;

        $segment = \array_merge(
            $this->commitItems($countBefore),
            [
                $startCommit,
            ],
            $this->commitItems($countBetween),
            [
                $endCommit,
            ]
        );

        $expectedItems = \array_slice(
            $segment,
            $countBefore + 1, // We don't want the first commit
            $countBetween + 1 // We want the commits in-between and the last commit
        );

        $commitApi
            ->expects($this->once())
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $endCommit['sha'])
            )
            ->willReturn($this->reverse($segment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);

        $commits = $range->commits();

        $this->assertCount(\count($expectedItems), $commits);

        \array_walk($commits, function ($commit) use (&$expectedItems) {
            /*
             * API returns items in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            $this->assertSame($expectedItem['sha'], $commit->sha());
            $this->assertSame($expectedItem['commit']['message'], $commit->message());
        });
    }

    public function testItemsFetchesMoreCommitsIfEndIsNotContainedInFirstBatch()
    {
        $faker = $this->faker();

        $repository = Resource\Repository::fromOwnerAndName(
            $faker->slug(),
            $faker->slug()
        );

        $startReference = $faker->sha1;
        $endReference = $faker->sha1;

        $commitApi = $this->createCommitApiMock();

        $startCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects($this->at(0))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($startReference)
            )
            ->willReturn($startCommit);

        $endCommit = $this->commitItem($faker->sha1);

        $commitApi
            ->expects($this->at(1))
            ->method('show')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->identicalTo($endReference)
            )
            ->willReturn($endCommit);

        $countBetweenFirstSegment = 4;
        $countBetweenSecondSegment = 5;

        $countBefore = 2;

        $firstSegment = \array_merge(
            $this->commitItems($countBetweenFirstSegment),
            [
                $endCommit,
            ]
        );

        $firstCommitFromFirstSegment = \reset($firstSegment);

        $secondSegment = \array_merge(
            $this->commitItems($countBefore),
            [
                $startCommit,
            ],
            $this->commitItems($countBetweenSecondSegment),
            [
                $firstCommitFromFirstSegment,
            ]
        );

        $expectedItems = \array_merge(
            \array_slice(
                $secondSegment,
                $countBefore + 1,
                $countBetweenSecondSegment
            ),
            $firstSegment
        );

        $commitApi
            ->expects($this->at(2))
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $endCommit['sha'])
            )
            ->willReturn($this->reverse($firstSegment));

        $commitApi
            ->expects($this->at(3))
            ->method('all')
            ->with(
                $this->identicalTo($repository->owner()),
                $this->identicalTo($repository->name()),
                $this->arrayHasKeyAndValue('sha', $firstCommitFromFirstSegment['sha'])
            )
            ->willReturn($this->reverse($secondSegment));

        $commitRepository = new Repository\CommitRepository($commitApi);

        $range = $commitRepository->items(
            $repository,
            $startReference,
            $endReference
        );

        $this->assertInstanceOf(Resource\RangeInterface::class, $range);

        $commits = $range->commits();

        $this->assertCount(\count($expectedItems), $commits);

        \array_walk($commits, function ($commit) use (&$expectedItems) {
            /*
             * API returns items in reverse order
             */
            $expectedItem = \array_pop($expectedItems);

            $this->assertInstanceOf(Resource\CommitInterface::class, $commit);

            /* @var Resource\CommitInterface $commit */
            $this->assertSame($expectedItem['sha'], $commit->sha());
            $this->assertSame($expectedItem['commit']['message'], $commit->message());
        });
    }

    /**
     * @return Api\Repository\Commits|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createCommitApiMock(): Api\Repository\Commits
    {
        return $this->createMock(Api\Repository\Commits::class);
    }

    private function commitItem(string $sha = null, string $message = null): array
    {
        $faker = $this->faker();

        return [
            'sha' => $sha ?: $faker->unique()->sha1,
            'commit' => [
                'message' => $message ?: $faker->unique()->sentence(),
            ],
        ];
    }

    /**
     * @param int $count
     *
     * @return array
     */
    private function commitItems(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; ++$i) {
            $items[] = $this->commitItem();
        }

        return $items;
    }

    /**
     * The GitHub API returns commits in reverse order!
     *
     * @param array $commits
     *
     * @return array
     */
    private function reverse(array $commits): array
    {
        return \array_reverse($commits);
    }

    private function arrayHasKeyAndValue(string $key, $value): Framework\Constraint\Callback
    {
        return $this->callback(function ($array) use ($key, $value) {
            if (\is_array($array)
                && \array_key_exists($key, $array)
                && $value === $array[$key]
            ) {
                return true;
            }

            return false;
        });
    }

    private function arrayNotHasKey(string $key): Framework\Constraint\Callback
    {
        return $this->callback(function ($array) use ($key) {
            if (\is_array($array)
                && !\array_key_exists($key, $array)
            ) {
                return true;
            }

            return false;
        });
    }
}
