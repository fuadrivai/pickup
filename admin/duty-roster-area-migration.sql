INSERT INTO duty_divisions (name)
VALUES ('Operasional')
ON DUPLICATE KEY UPDATE name = VALUES(name);

UPDATE duty_rosters
SET academic_year = CONCAT('Legacy-', id)
WHERE academic_year IS NULL OR TRIM(academic_year) = '';

CREATE TEMPORARY TABLE duty_roster_merge_map (
    source_id INT UNSIGNED NOT NULL PRIMARY KEY,
    target_id INT UNSIGNED NOT NULL
);

INSERT INTO duty_roster_merge_map (source_id, target_id)
SELECT src.id, MIN(dst.id)
FROM duty_rosters src
JOIN duty_rosters dst ON dst.academic_year = src.academic_year
GROUP BY src.id;

CREATE TEMPORARY TABLE duty_roster_merge_state (
    target_id INT UNSIGNED NOT NULL PRIMARY KEY,
    is_active TINYINT(1) NOT NULL
);

INSERT INTO duty_roster_merge_state (target_id, is_active)
SELECT mapping.target_id, MAX(roster.is_active)
FROM duty_roster_merge_map mapping
JOIN duty_rosters roster ON roster.id = mapping.source_id
GROUP BY mapping.target_id;

UPDATE duty_roster_slots slot
JOIN duty_roster_merge_map mapping ON mapping.source_id = slot.roster_id
SET slot.roster_id = mapping.target_id
WHERE mapping.source_id <> mapping.target_id;

UPDATE duty_rosters roster
JOIN duty_roster_merge_state state ON state.target_id = roster.id
SET roster.is_active = state.is_active;

DELETE roster
FROM duty_rosters roster
JOIN duty_roster_merge_map mapping ON mapping.source_id = roster.id
WHERE mapping.source_id <> mapping.target_id;

DROP TEMPORARY TABLE duty_roster_merge_state;
DROP TEMPORARY TABLE duty_roster_merge_map;

CREATE TEMPORARY TABLE duty_place_merge_map (
    source_id INT UNSIGNED NOT NULL PRIMARY KEY,
    target_id INT UNSIGNED NOT NULL
);

INSERT INTO duty_place_merge_map (source_id, target_id)
SELECT src.id, MIN(dst.id)
FROM duty_places src
JOIN duty_places dst
    ON TRIM(dst.name) = TRIM(src.name)
GROUP BY src.id;

UPDATE duty_roster_slots slot
JOIN duty_place_merge_map mapping ON mapping.source_id = slot.place_id
SET slot.place_id = mapping.target_id
WHERE mapping.source_id <> mapping.target_id;

DELETE place
FROM duty_places place
JOIN duty_place_merge_map mapping ON mapping.source_id = place.id
WHERE mapping.source_id <> mapping.target_id;

DROP TEMPORARY TABLE duty_place_merge_map;

ALTER TABLE duty_places
    DROP FOREIGN KEY fk_duty_places_division,
    DROP INDEX uq_duty_places_division_name,
    DROP COLUMN division_id,
    ADD UNIQUE KEY uq_duty_places_name (name);

ALTER TABLE duty_rosters
    DROP FOREIGN KEY fk_duty_rosters_division,
    DROP INDEX idx_duty_rosters_division,
    DROP COLUMN division_id,
    MODIFY COLUMN academic_year VARCHAR(30) NOT NULL,
    ADD UNIQUE KEY uq_duty_rosters_academic_year (academic_year);
