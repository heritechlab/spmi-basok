DROP TABLE IF EXISTS standard_document_types;
DROP TABLE IF EXISTS standard_statuses;
DROP TABLE IF EXISTS standard_categories;
DROP TABLE IF EXISTS standard_types;

CREATE TABLE standard_types (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(30) NOT NULL UNIQUE,

    name VARCHAR(150) NOT NULL,

    description TEXT,

    sort_order INT DEFAULT 0,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO standard_types
(code,name,description,sort_order)
VALUES

('SNDIKTI','Standar Nasional Pendidikan Tinggi',
'Standar Nasional Pendidikan Tinggi',1),

('SPMI','Standar Sistem Penjaminan Mutu Internal',
'Standar Internal Perguruan Tinggi',2),

('ISO21001','ISO 21001',
'Management System for Educational Organizations',3),

('AKREDITASI','Instrumen Akreditasi',
'Standar Akreditasi BANPT/LAMPTKes',4),

('INTERNAL','Standar Internal',
'Standar Internal Institusi',5);

CREATE TABLE standard_categories (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    type_id BIGINT UNSIGNED NOT NULL,

    code VARCHAR(30) NOT NULL,

    name VARCHAR(150) NOT NULL,

    description TEXT,

    sort_order INT DEFAULT 0,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_standard_category_type

        FOREIGN KEY(type_id)

        REFERENCES standard_types(id)

        ON UPDATE CASCADE

        ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO standard_categories
(type_id,code,name,sort_order)
VALUES

(2,'VMTS','Visi Misi Tujuan Sasaran',1),

(2,'TATA_PAMONG','Tata Pamong',2),

(2,'MAHASISWA','Mahasiswa',3),

(2,'SDM','Sumber Daya Manusia',4),

(2,'KEUANGAN','Keuangan',5),

(2,'SARPRAS','Sarana Prasarana',6),

(2,'PENDIDIKAN','Pendidikan',7),

(2,'PENELITIAN','Penelitian',8),

(2,'PKM','Pengabdian kepada Masyarakat',9),

(2,'LUARAN','Luaran Tridharma',10);

CREATE TABLE standard_statuses (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(30) UNIQUE,

    name VARCHAR(100),

    badge_color VARCHAR(30),

    description TEXT,

    sort_order INT DEFAULT 0,

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO standard_statuses
(code,name,badge_color,sort_order)
VALUES

('DRAFT','Draft','secondary',1),

('ACTIVE','Aktif','success',2),

('REVIEW','Review','warning',3),

('REVISED','Revisi','info',4),

('ARCHIVE','Arsip','dark',5);

CREATE TABLE standard_document_types (

    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(20),

    name VARCHAR(100),

    extension VARCHAR(20),

    mime_type VARCHAR(100),

    is_active TINYINT(1) DEFAULT 1,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO standard_document_types
(code,name,extension,mime_type)
VALUES

('PDF','Portable Document Format','pdf','application/pdf'),

('DOC','Microsoft Word','doc','application/msword'),

('DOCX','Microsoft Word OpenXML','docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document'),

('XLS','Microsoft Excel','xls','application/vnd.ms-excel'),

('XLSX','Microsoft Excel OpenXML','xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

