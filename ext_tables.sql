# noinspection SqlNoDataSourceInspectionForFile

#
# Table structure for table 'tx_t3importexport_domain_model_exporttarget'
#
CREATE TABLE tx_t3importexport_domain_model_exporttarget
(
		uid              int(11)                         NOT NULL auto_increment,
		pid              int(11)             DEFAULT '0' NOT NULL,

		description      text                            NOT NULL,
		title            tinytext                        NOT NULL,

		tstamp           int(11) unsigned    DEFAULT '0' NOT NULL,
		crdate           int(11) unsigned    DEFAULT '0' NOT NULL,
		cruser_id        int(11) unsigned    DEFAULT '0' NOT NULL,
		deleted          tinyint(4) unsigned DEFAULT '0' NOT NULL,
		hidden           tinyint(4) unsigned DEFAULT '0' NOT NULL,
		starttime        int(11) unsigned    DEFAULT '0' NOT NULL,
		endtime          int(11) unsigned    DEFAULT '0' NOT NULL,

		t3ver_oid        int(11)             DEFAULT '0' NOT NULL,
		t3ver_id         int(11)             DEFAULT '0' NOT NULL,
		t3ver_wsid       int(11)             DEFAULT '0' NOT NULL,
		t3ver_state      tinyint(4)          DEFAULT '0' NOT NULL,
		t3ver_stage      int(11)             DEFAULT '0' NOT NULL,
		t3ver_count      int(11)             DEFAULT '0' NOT NULL,
		t3ver_tstamp     int(11)             DEFAULT '0' NOT NULL,
		t3ver_move_id    int(11)             DEFAULT '0' NOT NULL,

		sys_language_uid int(11)             DEFAULT '0' NOT NULL,
		l10n_parent      int(11)             DEFAULT '0' NOT NULL,
		l10n_diffsource  mediumblob,

		PRIMARY KEY (uid),
		KEY parent (pid),
		KEY t3ver_oid (t3ver_oid, t3ver_wsid),
		KEY language (l10n_parent, sys_language_uid)
);


#
# Table structure for table 'tx_t3importexport_domain_model_queueitem'
# Queue of versions before 4.0. It is no longer used, but kept through 4.x so
# that the upgrade wizard t3importExport_queueItemMigration can read it (the
# database compare would rename a table that is not defined here). It will be
# removed in 5.0.
#
CREATE TABLE tx_t3importexport_domain_model_queueitem
(
		uid           int(11)                         NOT NULL auto_increment,
		pid           int(11)             DEFAULT '0' NOT NULL,

		created_date  int(11)             DEFAULT 0   NOT NULL,
		started_date  int(11)             DEFAULT 0   NOT NULL,
		finished_date int(11)             DEFAULT 0   NOT NULL,
		identifier varchar(255) DEFAULT '' NOT NULL comment 'identifier of an export or import task or set as registered in configuraion',
		checksum      varchar(255)        DEFAULT ''  NOT NULL,
		data          blob,
		status        tinyint(1) unsigned DEFAULT 0   NOT NULL COMMENT 'new: 0, processing: 1, finished: 2, error: 3',

		PRIMARY KEY (uid)
);

#
# Table structure for table 'tx_t3importexport_queue_item'
# Queue of cpsit/import-export-core (DbalQueueRepository). The table has no
# TCA; its structure must match DbalQueueRepository::getTables().
#
CREATE TABLE tx_t3importexport_queue_item
(
		id          bigint                    NOT NULL auto_increment,
		queue       varchar(255) DEFAULT ''   NOT NULL,
		checksum    varchar(40)  DEFAULT ''   NOT NULL,
		data        longtext,
		status      smallint     DEFAULT '0'  NOT NULL,
		claim_token varchar(32)  DEFAULT ''   NOT NULL,
		created_at  datetime     DEFAULT NULL,
		updated_at  datetime     DEFAULT NULL,

		PRIMARY KEY (id),
		UNIQUE KEY tx_t3importexport_queue_item_checksum (queue, checksum),
		KEY tx_t3importexport_queue_item_status (queue, status)
);
