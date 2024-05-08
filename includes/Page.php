<?php
/**
 * Hook into MediaWiki functionality.
 *
 * @file
 */

namespace MediaWiki\Extension\W4G\RatingBar;

class Page {
    public static function doesExist() {}

    public static function getID() {}

    public static function getName() {}

    public static function getFullName() {}

    public static function setID() {}

    public static function setName() {}

    public static function setFullName() {}

    /** Check if the given page ID is valid */
    public static function checkID() {
        $result = $dbslave->select('page', 'page_id,page_title',
        array('page_id' => $this->page_idnum),
        __METHOD__ ,
        array('LIMIT' => 1));
        if(!($row = $dbslave->fetchObject($result))) die('No page with this ID');
        $dbslave->freeResult($result);
    }

    public static function checkName() {}
}