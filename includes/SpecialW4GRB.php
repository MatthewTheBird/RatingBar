<?php
/**
 * Class that handles the *unlisted* special page of the extension.
 * Used for casting votes (ratings) and retrieving totals. I think.
 * -M3
 *
 * move content from W4GRB.php to this file
 *
 * @file
 */

namespace MediaWiki\Extension\W4G\RatingBar;

class SpecialW4GRB extends \UnlistedSpecialPage {

    public const ANONYMOUS_UID = 0;
    private $bar_id, $page_idnum, $uid;

    public function __construct() {
		parent::__construct( 'W4GRB' );
    }

    public function execute( $par = null ) {}

    private function justShowVotes() {}
}
