-- Prevent Double Booking Migration
-- Run this to add constraint that prevents duplicate active tokens per patient per session

-- Drop the previous constraint if it was added (it won't work properly with changing statuses)
-- ALTER TABLE opd_tokens DROP INDEX IF EXISTS unique_patient_session;

-- Add check in application logic (already implemented in opd-book.php)
-- This migration documents the business rule

-- Optional: Add a trigger to enforce at database level
DELIMITER //
CREATE TRIGGER prevent_double_booking
BEFORE INSERT ON opd_tokens
FOR EACH ROW
BEGIN
    DECLARE existing_count INT;
    
    -- Check if patient already has an active token for this session
    SELECT COUNT(*) INTO existing_count
    FROM opd_tokens
    WHERE patient_id = NEW.patient_id 
      AND session_id = NEW.session_id
      AND status NOT IN ('cancelled', 'no-show');
    
    IF existing_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Patient already has an active token for this session';
    END IF;
END//
DELIMITER ;
