use itmo544db;
CREATE TABLE records (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,email VARCHAR(32),phone VARCHAR(32),s3_raw_url VARCHAR(100),s3_finished_url VARCHAR(100),uid VARCHAR(100), status INT(1), reciept BIGINT, dateprocessed DATE);
exit