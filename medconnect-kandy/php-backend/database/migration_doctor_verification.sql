-- Migration: Add doctor verification fields
ALTER TABLE users ADD COLUMN slmc_registration VARCHAR(50) NULL AFTER specialization;
ALTER TABLE users ADD COLUMN nic_number VARCHAR(20) NULL AFTER slmc_registration;
ALTER TABLE users ADD COLUMN hospital_name VARCHAR(255) NULL AFTER nic_number;
ALTER TABLE users ADD COLUMN verification_document VARCHAR(255) NULL AFTER hospital_name;
