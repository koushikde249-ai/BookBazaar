USE bookbazaar;

-- Run this migration ONCE after importing database.sql.
ALTER TABLE books ADD COLUMN is_sold TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

CREATE TABLE IF NOT EXISTS orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT UNSIGNED NOT NULL,
  seller_id INT UNSIGNED NOT NULL,
  book_id INT UNSIGNED NOT NULL,
  book_title VARCHAR(150) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  buyer_name VARCHAR(100) NOT NULL,
  buyer_email VARCHAR(190) NOT NULL,
  buyer_phone VARCHAR(25) NOT NULL,
  delivery_address TEXT NOT NULL,
  delivery_note VARCHAR(500) NULL,
  payment_method ENUM('cod','razorpay') NOT NULL,
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  order_status ENUM('placed','confirmed','completed','cancelled') NOT NULL DEFAULT 'placed',
  razorpay_order_id VARCHAR(100) NULL,
  razorpay_payment_id VARCHAR(100) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_orders_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_orders_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  INDEX idx_orders_buyer (buyer_id), INDEX idx_orders_seller (seller_id), INDEX idx_orders_status (order_status)
) ENGINE=InnoDB;
