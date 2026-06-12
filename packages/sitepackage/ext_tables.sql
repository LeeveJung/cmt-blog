CREATE TABLE tt_content (
    tx_sitepackage_vertical_align varchar(20) DEFAULT 'items-center' NOT NULL,
    tx_sitepackage_show_divider tinyint(1) DEFAULT 0 NOT NULL,
    tx_sitepackage_no_padding tinyint(1) DEFAULT 0 NOT NULL
);

CREATE TABLE sys_file_reference (
    tx_sitepackage_loading varchar(10) DEFAULT '' NOT NULL,
    tx_sitepackage_fetchpriority varchar(10) DEFAULT '' NOT NULL
);
