CREATE TABLE IF NOT EXISTS /*_*/w4grb_cat_avg (
	`page` varchar(100) unique NOT NULL,
	`avg` float unsigned NOT NULL,
	`n` int(10) unsigned NOT NULL,
	`time` int(11) unsigned NOT NULL,
	PRIMARY KEY  (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
