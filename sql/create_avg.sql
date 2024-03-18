CREATE TABLE IF NOT EXISTS /*_*/w4grb_avg (
  `pid` int(10) unsigned NOT NULL,
  `avg` float unsigned NOT NULL,
  `n` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
