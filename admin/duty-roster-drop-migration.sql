-- DANGER: This permanently deletes all Duty Roster data.
-- This script only drops Duty Roster tables; it does not alter display_contents.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
    duty_roster_assignments,
    duty_roster_slots,
    duty_rosters,
    duty_places,
    duty_events,
    duty_teachers,
    duty_divisions;

SET FOREIGN_KEY_CHECKS = 1;
