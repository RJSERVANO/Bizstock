CREATE TABLE users (
 user_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(100) NOT NULL,
 username VARCHAR(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 password_hash VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 role VARCHAR(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 is_active TINYINT NOT NULL DEFAULT 1,
 session_version INT NOT NULL DEFAULT 1,
 must_change_password TINYINT NOT NULL DEFAULT 1,
 created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 CONSTRAINT uq_users_username UNIQUE(username),
 CONSTRAINT ck_users_name CHECK(CHAR_LENGTH(TRIM(full_name))>0),
 CONSTRAINT ck_users_username CHECK(username=LOWER(username) AND CHAR_LENGTH(username)>0 AND username NOT REGEXP '[^a-z0-9._-]'),
 CONSTRAINT ck_users_hash CHECK(CHAR_LENGTH(password_hash)>=20),
 CONSTRAINT ck_users_role CHECK(role IN ('Admin','Staff','Viewer')),
 CONSTRAINT ck_users_active CHECK(is_active IN (0,1)),
 CONSTRAINT ck_users_session CHECK(session_version>0),
 CONSTRAINT ck_users_change CHECK(must_change_password IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
 category_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 category_name VARCHAR(100) NOT NULL,
 CONSTRAINT uq_categories_name UNIQUE(category_name),
 CONSTRAINT ck_categories_name CHECK(CHAR_LENGTH(TRIM(category_name))>0 AND OCTET_LENGTH(category_name)=OCTET_LENGTH(TRIM(category_name)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
 supplier_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 supplier_name VARCHAR(150) NOT NULL,
 contact_email VARCHAR(100) NULL,
 created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 CONSTRAINT ck_suppliers_name CHECK(CHAR_LENGTH(TRIM(supplier_name))>0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
 product_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 sku VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 product_name VARCHAR(150) NOT NULL,
 location VARCHAR(100) NULL,
 description TEXT NULL,
 category_id INT NOT NULL,
 supplier_id INT NOT NULL,
 price DECIMAL(10,2) NOT NULL,
 quantity INT NOT NULL DEFAULT 0,
 minimum_threshold INT NOT NULL DEFAULT 10,
 version INT NOT NULL DEFAULT 1,
 is_active TINYINT NOT NULL DEFAULT 1,
 created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 CONSTRAINT uq_products_sku UNIQUE(sku),
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
 CONSTRAINT fk_products_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(supplier_id) ON DELETE RESTRICT,
 CONSTRAINT ck_products_sku CHECK(sku=UPPER(sku) AND CHAR_LENGTH(sku)>0 AND sku NOT REGEXP '[^A-Z0-9._-]'),
 CONSTRAINT ck_products_name CHECK(CHAR_LENGTH(TRIM(product_name))>0),
 CONSTRAINT ck_products_price CHECK(price>=0),
 CONSTRAINT ck_products_quantity CHECK(quantity>=0),
 CONSTRAINT ck_products_threshold CHECK(minimum_threshold>=0),
 CONSTRAINT ck_products_version CHECK(version>0),
 CONSTRAINT ck_products_active CHECK(is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
 transaction_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 product_id INT NOT NULL,
 user_id INT NOT NULL,
 type VARCHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 quantity INT NOT NULL,
 unit_price DECIMAL(10,2) NOT NULL,
 transaction_date DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 request_key VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 payload_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 correction_of_transaction_id INT NULL,
 notes TEXT NULL,
 CONSTRAINT fk_transactions_product FOREIGN KEY(product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
 CONSTRAINT fk_transactions_user FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE RESTRICT,
 CONSTRAINT fk_transactions_correction FOREIGN KEY(correction_of_transaction_id) REFERENCES transactions(transaction_id) ON DELETE RESTRICT,
 CONSTRAINT uq_transactions_request UNIQUE(user_id,request_key),
 CONSTRAINT uq_transactions_correction UNIQUE(correction_of_transaction_id),
 CONSTRAINT ck_transactions_type CHECK(type IN ('IN','OUT')),
 CONSTRAINT ck_transactions_quantity CHECK(quantity>0),
 CONSTRAINT ck_transactions_price CHECK(unit_price>=0),
 CONSTRAINT ck_transactions_key CHECK(CHAR_LENGTH(TRIM(request_key))>0 AND OCTET_LENGTH(request_key)=OCTET_LENGTH(TRIM(request_key))),
 CONSTRAINT ck_transactions_hash CHECK(CHAR_LENGTH(payload_hash)=64 AND payload_hash NOT REGEXP '[^0-9a-f]')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
