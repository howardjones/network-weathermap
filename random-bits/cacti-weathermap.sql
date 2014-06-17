/* You should never need this file during installation. If you DO, please report a bug. */

CREATE TABLE IF NOT EXISTS weathermap_maps (
  id           INT(11)                  NOT NULL AUTO_INCREMENT,
  sortorder    INT(11)                  NOT NULL DEFAULT 0,
  group_id     INT(11)                  NOT NULL DEFAULT 1,
  active       SET('on', 'off')         NOT NULL DEFAULT 'on',
  configfile   TEXT                     NOT NULL,
  imagefile    TEXT                     NOT NULL,
  htmlfile     TEXT                     NOT NULL,
  titlecache   TEXT                     NOT NULL,
  filehash     VARCHAR(40)              NOT NULL DEFAULT '',
  warncount    INT(11)                  NOT NULL DEFAULT 0,
  debug        SET('on', 'off', 'once') NOT NULL DEFAULT 'off',
  runtime      DOUBLE                   NOT NULL DEFAULT 0,
  lastrun      DATETIME,
  config       TEXT                     NOT NULL DEFAULT '',
  thumb_width  INT(11)                  NOT NULL DEFAULT 0,
  thumb_height INT(11)                  NOT NULL DEFAULT 0,
  schedule     VARCHAR(32)              NOT NULL DEFAULT '*',
  archiving    SET('on', 'off')         NOT NULL DEFAULT 'off',
  PRIMARY KEY (id)
)
  ENGINE =MyISAM;

CREATE TABLE IF NOT EXISTS weathermap_auth (
  userid MEDIUMINT(9) NOT NULL DEFAULT '0',
  mapid  INT(11)      NOT NULL DEFAULT '0'
)
  ENGINE =MyISAM;

CREATE TABLE IF NOT EXISTS weathermap_groups (
  `id`        INT(11)      NOT NULL AUTO_INCREMENT,
  `name`      VARCHAR(128) NOT NULL DEFAULT '',
  `sortorder` INT(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
)
  ENGINE =MyISAM;

INSERT INTO weathermap_groups (id, name, sortorder) VALUES (1, 'Weathermaps', 1);

CREATE TABLE IF NOT EXISTS weathermap_settings (
  id       INT(11)      NOT NULL AUTO_INCREMENT,
  mapid    INT(11)      NOT NULL DEFAULT '0',
  groupid  INT(11)      NOT NULL DEFAULT '0',
  optname  VARCHAR(128) NOT NULL DEFAULT '',
  optvalue VARCHAR(128) NOT NULL DEFAULT '',
  PRIMARY KEY (id)
)
  ENGINE =MyISAM;

CREATE TABLE IF NOT EXISTS weathermap_data (id        INT(11)      NOT NULL AUTO_INCREMENT,
                                            rrdfile   VARCHAR(255) NOT NULL, data_source_name VARCHAR(19) NOT NULL,
                                            last_time INT(11)      NOT NULL, last_value VARCHAR(255) NOT NULL,
                                            last_calc VARCHAR(255) NOT NULL, sequence INT(11) NOT NULL, local_data_id INT(11) NOT NULL DEFAULT 0, PRIMARY KEY (id), KEY rrdfile (rrdfile),
  KEY local_data_id (local_data_id), KEY data_source_name (data_source_name))
  ENGINE =MyISAM;