CREATE TABLE security_locks (
 lock_id INT NOT NULL PRIMARY KEY,
 CONSTRAINT ck_security_singleton CHECK(lock_id=1)
) ENGINE=InnoDB;
INSERT INTO security_locks(lock_id) VALUES (1);

CREATE TABLE operation_requests (
 operation_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 actor_id INT NOT NULL,
 operation_kind VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 request_key VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 payload_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 product_id INT NULL,
 transaction_id INT NULL,
 response_status SMALLINT NULL,
 response_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
 created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 CONSTRAINT uq_operations_request UNIQUE(actor_id,request_key),
 CONSTRAINT fk_operations_actor FOREIGN KEY(actor_id) REFERENCES users(user_id) ON DELETE RESTRICT,
 CONSTRAINT fk_operations_product FOREIGN KEY(product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
 CONSTRAINT fk_operations_transaction FOREIGN KEY(transaction_id) REFERENCES transactions(transaction_id) ON DELETE RESTRICT,
 CONSTRAINT ck_operations_kind CHECK(CHAR_LENGTH(TRIM(operation_kind))>0),
 CONSTRAINT ck_operations_key CHECK(CHAR_LENGTH(TRIM(request_key))>0 AND OCTET_LENGTH(request_key)=OCTET_LENGTH(TRIM(request_key))),
 CONSTRAINT ck_operations_hash CHECK(CHAR_LENGTH(payload_hash)=64 AND payload_hash NOT REGEXP '[^0-9a-f]'),
 CONSTRAINT ck_operations_status CHECK(response_status IS NULL OR response_status BETWEEN 200 AND 299),
 CONSTRAINT ck_operations_json CHECK(response_json IS NULL OR JSON_VALID(response_json)),
 CONSTRAINT ck_operations_complete CHECK((response_status IS NULL AND response_json IS NULL) OR (response_status IS NOT NULL AND response_json IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_events (
 event_id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 actor_id INT NULL,
 action VARCHAR(60) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 entity_type VARCHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 entity_id BIGINT NULL,
 before_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
 after_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
 occurred_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 request_id VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 CONSTRAINT fk_audit_actor FOREIGN KEY(actor_id) REFERENCES users(user_id) ON DELETE RESTRICT,
 CONSTRAINT ck_audit_action CHECK(CHAR_LENGTH(TRIM(action))>0),
 CONSTRAINT ck_audit_entity CHECK(CHAR_LENGTH(TRIM(entity_type))>0 AND (entity_id IS NULL OR entity_id>0)),
 CONSTRAINT ck_audit_before CHECK(before_json IS NULL OR JSON_VALID(before_json)),
 CONSTRAINT ck_audit_after CHECK(after_json IS NULL OR JSON_VALID(after_json)),
 CONSTRAINT ck_audit_request CHECK(CHAR_LENGTH(TRIM(request_id))>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
 limiter_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 window_started_at DATETIME(6) NOT NULL,
 reset_at DATETIME(6) NOT NULL,
 attempts INT NOT NULL DEFAULT 0,
 CONSTRAINT ck_rate_key CHECK(CHAR_LENGTH(limiter_key)=64 AND limiter_key NOT REGEXP '[^0-9a-f]'),
 CONSTRAINT ck_rate_attempts CHECK(attempts>=0),
 CONSTRAINT ck_rate_window CHECK(reset_at>window_started_at)
) ENGINE=InnoDB;
