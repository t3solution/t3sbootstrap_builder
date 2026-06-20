CREATE TABLE tx_t3sbootstrapbuilder_theme (
    title varchar(255) DEFAULT '' NOT NULL,
    site_identifier varchar(255) DEFAULT '' NOT NULL,
    root_page_uid int(11) unsigned DEFAULT '0' NOT NULL,
    base_preset varchar(64) DEFAULT '' NOT NULL,
    variables_json mediumtext,
    custom_scss mediumtext
);
