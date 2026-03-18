CREATE TABLE fe_users (
    `md5_hash` VARCHAR(255) DEFAULT '' NOT NULL,
    `birthday` VARCHAR(10) DEFAULT '' NOT NULL,
    `admin` TINYINT(4) DEFAULT 0 NOT NULL,
    `description` text,
    `profilbeschreibung` text,
    `signature` text
);


CREATE TABLE fe_groups (
    group_color VARCHAR(7) DEFAULT '' NOT NULL
);


CREATE TABLE tx_forumman_domain_model_message (
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);