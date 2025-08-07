<?php
/**
 * Special *unlisted* page that handles voting and vote counting.
 *
 * @file
 *
 * - __construct
 * - execute
 * - justShowVotes
 */

namespace MediaWiki\Extension\W4G\RatingBar;

/*********************************************************************
**
** This file is part of the W4G Rating Bar extension for MediaWiki
** Copyright (C)2011
**                - David Dernoncourt <www.patheticcockroach.com>
**                - Franck Dernoncourt <www.francky.me>
**
** Home Page: http://www.wiki4games.com/Wiki4Games:W4G Rating Bar
**
** This program is licensed under the Creative Commons
** Attribution-ShareAlike 4.0 license
** <https://creativecommons.org/licenses/by-sa/4.0/>
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
**
*********************************************************************/

class W4GRB extends UnlistedSpecialPage
