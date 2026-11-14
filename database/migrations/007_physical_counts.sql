CREATE TABLE stock_counts (
 count_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 created_by INT NOT NULL,
 approved_by INT NULL,
 status VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'draft',
 version INT NOT NULL DEFAULT 1,
 note VARCHAR(500) NULL,
 created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 approved_at DATETIME(6) NULL,
 CONSTRAINT fk_counts_creator FOREIGN KEY(created_by) REFERENCES users(user_id) ON DELETE RESTRICT,
 CONSTRAINT fk_counts_approver FOREIGN KEY(approved_by) REFERENCES users(user_id) ON DELETE RESTRICT,
 CONSTRAINT ck_counts_status CHECK(status IN ('draft','submitted','approved','cancelled')),
 CONSTRAINT ck_counts_version CHECK(version>0),
 CONSTRAINT ck_counts_approval CHECK((status='approved' AND approved_by IS NOT NULL AND approved_at IS NOT NULL) OR (status<>'approved' AND approved_by IS NULL AND approved_at IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE stock_count_lines (
 count_id INT NOT NULL,
 product_id INT NOT NULL,
 sku_snapshot VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 product_name_snapshot VARCHAR(150) NOT NULL,
 unit_code VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 expected_quantity INT NOT NULL,
 expected_revision INT NOT NULL,
 counted_quantity INT NOT NULL,
 reason VARCHAR(500) NULL,
 adjustment_transaction_id INT NULL,
 PRIMARY KEY(count_id,product_id),
 CONSTRAINT uq_count_adjustment UNIQUE(adjustment_transaction_id),
 CONSTRAINT fk_count_lines_count FOREIGN KEY(count_id) REFERENCES stock_counts(count_id) ON DELETE RESTRICT,
 CONSTRAINT fk_count_lines_product FOREIGN KEY(product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
 CONSTRAINT fk_count_lines_adjustment FOREIGN KEY(adjustment_transaction_id) REFERENCES transactions(transaction_id) ON DELETE RESTRICT,
 CONSTRAINT ck_count_lines_quantity CHECK(expected_quantity>=0 AND counted_quantity>=0),
 CONSTRAINT ck_count_lines_revision CHECK(expected_revision>0),
 CONSTRAINT ck_count_lines_reason CHECK(counted_quantity=expected_quantity OR (reason IS NOT NULL AND CHAR_LENGTH(TRIM(reason))>0)),
 CONSTRAINT ck_count_lines_unit CHECK(unit_code IN ('unspecified','piece','box','kg','litre','metre','pair','set'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_counts_status_date ON stock_counts(status,created_at,count_id);
CREATE INDEX idx_count_lines_product ON stock_count_lines(product_id,count_id);
