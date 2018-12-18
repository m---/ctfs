-- database
CREATE DATABASE mage;
USE mage;

CREATE TABLE users (
    user_id VARCHAR(128) NOT NULL,
    password VARCHAR(128) NOT NULL,
    balance INTEGER(11) NOT NULL,
    reported TINYINT(1) NOT NULL,
    PRIMARY KEY(user_id)
);

CREATE TABLE transactor (
    transactor_id INTEGER(11) NOT NULL AUTO_INCREMENT,
    src_user_id VARCHAR(128),
    dst_user_id VARCHAR(128) NOT NULL,
    code VARCHAR(64) NOT NULL UNIQUE,
    PRIMARY KEY(transactor_id)
);

CREATE TABLE account (
    account_id INTEGER(11) NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(128) NOT NULL,
    debit INTEGER(11) NOT NULL,
    credit INTEGER(11) NOT NULL,
    notes VARCHAR(255) NOT NULL,
    PRIMARY KEY(account_id)
);

CREATE TABLE admin_log (
    admin_log_id INTEGER(11) NOT NULL AUTO_INCREMENT,
    user_id VARCHAR(128) NOT NULL,
    user_key VARCHAR(128) NOT NULL,
    type ENUM('transfer', 'message') NOT NULL,
    data VARCHAR(255) NOT NULL,
    create_time DATETIME NOT NULL,
    PRIMARY KEY(admin_log_id)
);

CREATE TABLE flag1 (
    flag1 VARCHAR(255) NOT NULL
);

INSERT INTO flag1 (flag1) VALUES ('CBCTF{If_You_w4n7_a_fL46,_W0rk_4nd_34rn_m0N3y}'); -- Can't guess

-- user
CREATE USER 'mage'@'%' IDENTIFIED BY 'password'; -- Can't guess
CREATE USER 'admin'@'%' IDENTIFIED BY 'password'; -- Can't guess
GRANT SELECT,UPDATE,INSERT,DELETE ON mage.users TO 'mage'@'%';
GRANT SELECT,UPDATE,INSERT,DELETE ON mage.transactor TO 'mage'@'%';
GRANT SELECT,UPDATE,INSERT,DELETE ON mage.account TO 'mage'@'%';
GRANT SELECT,UPDATE,INSERT,DELETE ON mage.flag1 TO 'mage'@'%';
GRANT SELECT,UPDATE,INSERT,DELETE ON mage.admin_log TO 'admin'@'%';
FLUSH PRIVILEGES;
