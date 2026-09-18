ALTER TABLE standards

ADD CONSTRAINT fk_standard_type

FOREIGN KEY (type_id)

REFERENCES standard_types(id)

ON UPDATE CASCADE

ON DELETE RESTRICT;

ALTER TABLE standards

ADD CONSTRAINT fk_standard_category

FOREIGN KEY(category_id)

REFERENCES standard_categories(id)

ON UPDATE CASCADE

ON DELETE RESTRICT;

ALTER TABLE standards

ADD CONSTRAINT fk_standard_status

FOREIGN KEY(status_id)

REFERENCES standard_statuses(id)

ON UPDATE CASCADE

ON DELETE RESTRICT;

CREATE INDEX idx_standard_type
ON standards(type_id);

CREATE INDEX idx_standard_category
ON standards(category_id);

CREATE INDEX idx_standard_status
ON standards(status_id);

