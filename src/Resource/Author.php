<?php

/*
 * Copyright (c) 2016 Andreas Möller <am@localheinz.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Localheinz\GitHub\ChangeLog\Resource;

use Assert\Assertion;

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
     */
    public function __construct($login, $htmlUrl)
    {
        Assertion::string($login);
        Assertion::string($htmlUrl);
        Assertion::url($htmlUrl);

        $this->login = $login;
        $this->htmlUrl = $htmlUrl;
    }

    public function login()
    {
        return $this->login;
    }

    public function htmlUrl()
    {
        return $this->htmlUrl;
    }
}
