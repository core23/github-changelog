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

namespace Localheinz\GitHub\ChangeLog\Resource;

use Assert;

final class Author implements AuthorInterface
{
    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $htmlUrl;

    /**
     * @param string $login
     * @param string $htmlUrl
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(string $login, string $htmlUrl)
    {
        Assert\that($htmlUrl)->url();

        $this->login = $login;
        $this->htmlUrl = $htmlUrl;
    }

    public function login(): string
    {
        return $this->login;
    }

    public function htmlUrl(): string
    {
        return $this->htmlUrl;
    }
}
