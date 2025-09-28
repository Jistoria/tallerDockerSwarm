-- Database: store (create it if you run locally with psql)
-- CREATE DATABASE store;

-- === Extensions (optional) ===
-- Uncomment if you want case-insensitive emails (requires superuser):
-- CREATE EXTENSION IF NOT EXISTS citext;

-- === Table: users ===
CREATE TABLE IF NOT EXISTS users (
  id           BIGSERIAL PRIMARY KEY,
  name         TEXT        NOT NULL,
  email        TEXT        NOT NULL UNIQUE, -- keep simple; enforce lowercase in application if needed
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- === Table: products ===
CREATE TABLE IF NOT EXISTS products (
  id           BIGSERIAL PRIMARY KEY,
  sku          TEXT        UNIQUE,
  name         TEXT        NOT NULL,
  price        NUMERIC(12,2) NOT NULL CHECK (price >= 0),
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_products_name ON products (name);

-- === Table: sales ===
-- We store unit_price at sale time (immutable snapshot).
-- `total` is a generated column = quantity * unit_price.
CREATE TABLE IF NOT EXISTS sales (
  id           BIGSERIAL PRIMARY KEY,
  user_id      BIGINT     NOT NULL REFERENCES users(id)    ON DELETE RESTRICT,
  product_id   BIGINT     NOT NULL REFERENCES products(id) ON DELETE RESTRICT,
  quantity     INT        NOT NULL CHECK (quantity > 0),
  unit_price   NUMERIC(12,2) NOT NULL CHECK (unit_price >= 0),
  total        NUMERIC(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
  created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sales_user    ON sales (user_id);
CREATE INDEX IF NOT EXISTS idx_sales_product ON sales (product_id);
CREATE INDEX IF NOT EXISTS idx_sales_created ON sales (created_at);

-- === Seed (optional) ===
INSERT INTO users (name, email) VALUES
  ('Alice', 'alice@example.com'),
  ('Bob',   'bob@example.com')
ON CONFLICT (email) DO NOTHING;

INSERT INTO products (sku, name, price) VALUES
  ('SKU-001', 'Notebook', 1.50),
  ('SKU-002', 'Blue Pen', 0.80)
ON CONFLICT (sku) DO NOTHING;

-- Example sale (uses snapshot unit_price at the time of sale)
-- Insert only if the references exist:
DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE email='alice@example.com')
     AND EXISTS (SELECT 1 FROM products WHERE sku='SKU-001') THEN
    INSERT INTO sales (user_id, product_id, quantity, unit_price)
    SELECT u.id, p.id, 3, p.price
    FROM users u, products p
    WHERE u.email='alice@example.com' AND p.sku='SKU-001'
    ON CONFLICT DO NOTHING;
  END IF;
END$$;
