CREATE TABLE karsilastirma_kararlari (
    kod VARCHAR(10) PRIMARY KEY,
    karar VARCHAR(20) NOT NULL DEFAULT 'bekliyor',
    notlar TEXT,
    guncelleyen_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
