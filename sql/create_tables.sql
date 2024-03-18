CREATE TABLE IF NOT EXISTS /*_*/w4grb_votes (
  `uid` int(11) unsigned NOT NULL,
  `pid` int(11) unsigned NOT NULL,
  `vote` tinyint(4) unsigned NOT NULL,
  `ip` varbinary(39) NOT NULL,
  `time` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`uid`,`pid`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE IF NOT EXISTS /*_*/w4grb_avg (
  `pid` int(10) unsigned NOT NULL,
  `avg` float unsigned NOT NULL,
  `n` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

CREATE TABLE IF NOT EXISTS /*_*/w4grb_cat_avg (
  `page` varchar(100) unique NOT NULL,
  `avg` float unsigned NOT NULL,
  `n` int(10) unsigned NOT NULL,
  `time` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`page`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

