<?php
/**
 * Thie file is part of the W4G Rating Bar extension for MediaWiki
 * Copyright (C) 2011
 * @author David Dernoncourt <www.patheticcockroach.com>
 * @author Franck Dernoncourt <www.francky.me>
 *
 * Home Page: <http://www.wiki4games.com/Wiki4Games:W4G Rating Bar
 *
 * @copyright This program is license under the Creative Commons
 * @copyright Attribution-SharAlike 4.0 License
 * @copyright <https://creativecommons.org/licenses/by-sa/4.0/>
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; even WITHOUT IMPLIED WARRANTY OF
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\W4G\RatingBar;

/**
 * @brief Handle actions sent to unlisted page.
 *
 * @details Processes data passed to the unlisted special page for processing by the extension.
 *
 * @ingroup Extensions
 */

class SpecialW4GRB extends UnlistedSpecialPage {

    /** @var int ANONYMOUS_UID ID of anonymous user? */
    public const ANONYMOUS_UID = 0;

    /**
     * @var int $bar_id ID of rating bar?
     * @var int $page_idnum ID of page?
     * @var int $uid ID of user?
     */
    private $bar_id, $page_idnum, $uid;

    public function __construct() {
		parent::__construct( 'W4GRB' );
    }

    public function execute( $par = null ) {}

    private function justShowVotes() {}
}
