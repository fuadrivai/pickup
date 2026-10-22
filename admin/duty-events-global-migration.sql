ALTER TABLE duty_events
    DROP FOREIGN KEY fk_duty_events_division,
    DROP INDEX uq_duty_events_division_name,
    DROP COLUMN division_id,
    ADD UNIQUE KEY uq_duty_events_name (name);
