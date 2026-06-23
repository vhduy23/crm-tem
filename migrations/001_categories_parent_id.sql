-- Danh mục 2 tầng: thêm cột parent_id (NULL = danh mục cha)
ALTER TABLE categories
    ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER name,
    ADD INDEX idx_categories_parent (parent_id),
    ADD CONSTRAINT fk_categories_parent
        FOREIGN KEY (parent_id) REFERENCES categories(id)
        ON DELETE RESTRICT ON UPDATE CASCADE;
