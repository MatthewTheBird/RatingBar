CREATE TABLE IF NOT EXISTS /*_*/w4grb_votes (
  `uid` int(11) unsigned NOT NULL,
  `pid` int(11) unsigned NOT NULL,
  `vote` tinyint(4) unsigned NOT NULL,
  `ip` varbinary(39) NOT NULL,
  `time` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`uid`,`pid`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
