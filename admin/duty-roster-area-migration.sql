DROP PROCEDURE IF EXISTS migrate_duty_roster_global_areas;
DELIMITER $$
CREATE PROCEDURE migrate_duty_roster_global_areas()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE constraint_name_value VARCHAR(64);
    DECLARE index_name_value VARCHAR(64);
    DECLARE has_place_division INT DEFAULT 0;
    DECLARE has_roster_division INT DEFAULT 0;
    DECLARE has_academic_year INT DEFAULT 0;
    DECLARE has_place_unique_name INT DEFAULT 0;
    DECLARE has_roster_unique_year INT DEFAULT 0;

    DECLARE place_foreign_keys CURSOR FOR
        SELECT DISTINCT constraint_name
        FROM information_schema.key_column_usage
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_places'
          AND column_name = 'division_id'
          AND referenced_table_name IS NOT NULL;

    DECLARE place_division_indexes CURSOR FOR
        SELECT DISTINCT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_places'
          AND column_name = 'division_id'
          AND index_name <> 'PRIMARY';

    DECLARE roster_foreign_keys CURSOR FOR
        SELECT DISTINCT constraint_name
        FROM information_schema.key_column_usage
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_rosters'
          AND column_name = 'division_id'
          AND referenced_table_name IS NOT NULL;

    DECLARE roster_division_indexes CURSOR FOR
        SELECT DISTINCT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_rosters'
          AND column_name = 'division_id'
          AND index_name <> 'PRIMARY';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    INSERT INTO duty_divisions (name)
    VALUES ('Operasional')
    ON DUPLICATE KEY UPDATE name = VALUES(name);

    SELECT COUNT(*) INTO has_academic_year
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'duty_rosters'
      AND column_name = 'academic_year';

    IF has_academic_year > 0 THEN
        UPDATE duty_rosters
        SET academic_year = CONCAT('Legacy-', id)
        WHERE academic_year IS NULL OR TRIM(academic_year) = '';

        DROP TEMPORARY TABLE IF EXISTS duty_roster_merge_map;
        CREATE TEMPORARY TABLE duty_roster_merge_map (
            source_id INT UNSIGNED NOT NULL PRIMARY KEY,
            target_id INT UNSIGNED NOT NULL
        );

        INSERT INTO duty_roster_merge_map (source_id, target_id)
        SELECT src.id, MIN(dst.id)
        FROM duty_rosters src
        JOIN duty_rosters dst ON dst.academic_year = src.academic_year
        GROUP BY src.id;

        DROP TEMPORARY TABLE IF EXISTS duty_roster_merge_state;
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
    END IF;

    DROP TEMPORARY TABLE IF EXISTS duty_place_merge_map;
    CREATE TEMPORARY TABLE duty_place_merge_map (
        source_id INT UNSIGNED NOT NULL PRIMARY KEY,
        target_id INT UNSIGNED NOT NULL
    );

    INSERT INTO duty_place_merge_map (source_id, target_id)
    SELECT src.id, MIN(dst.id)
    FROM duty_places src
    JOIN duty_places dst ON TRIM(dst.name) = TRIM(src.name)
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

    OPEN place_foreign_keys;
    place_foreign_key_loop: LOOP
        FETCH place_foreign_keys INTO constraint_name_value;
        IF done THEN
            LEAVE place_foreign_key_loop;
        END IF;
        SET @migration_sql = CONCAT(
            'ALTER TABLE `duty_places` DROP FOREIGN KEY `',
            REPLACE(constraint_name_value, '`', '``'),
            '`'
        );
        PREPARE migration_statement FROM @migration_sql;
        EXECUTE migration_statement;
        DEALLOCATE PREPARE migration_statement;
    END LOOP;
    CLOSE place_foreign_keys;

    SET done = 0;
    OPEN place_division_indexes;
    place_index_loop: LOOP
        FETCH place_division_indexes INTO index_name_value;
        IF done THEN
            LEAVE place_index_loop;
        END IF;
        SET @migration_sql = CONCAT(
            'ALTER TABLE `duty_places` DROP INDEX `',
            REPLACE(index_name_value, '`', '``'),
            '`'
        );
        PREPARE migration_statement FROM @migration_sql;
        EXECUTE migration_statement;
        DEALLOCATE PREPARE migration_statement;
    END LOOP;
    CLOSE place_division_indexes;

    SELECT COUNT(*) INTO has_place_division
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'duty_places'
      AND column_name = 'division_id';

    IF has_place_division > 0 THEN
        ALTER TABLE duty_places DROP COLUMN division_id;
    END IF;

    SELECT COUNT(*) INTO has_place_unique_name
    FROM (
        SELECT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_places'
          AND non_unique = 0
        GROUP BY index_name
        HAVING COUNT(*) = 1 AND MAX(column_name) = 'name'
    ) AS place_name_indexes;

    IF has_place_unique_name = 0 THEN
        ALTER TABLE duty_places ADD UNIQUE KEY uq_duty_places_name (name);
    END IF;

    SET done = 0;
    OPEN roster_foreign_keys;
    roster_foreign_key_loop: LOOP
        FETCH roster_foreign_keys INTO constraint_name_value;
        IF done THEN
            LEAVE roster_foreign_key_loop;
        END IF;
        SET @migration_sql = CONCAT(
            'ALTER TABLE `duty_rosters` DROP FOREIGN KEY `',
            REPLACE(constraint_name_value, '`', '``'),
            '`'
        );
        PREPARE migration_statement FROM @migration_sql;
        EXECUTE migration_statement;
        DEALLOCATE PREPARE migration_statement;
    END LOOP;
    CLOSE roster_foreign_keys;

    SET done = 0;
    OPEN roster_division_indexes;
    roster_index_loop: LOOP
        FETCH roster_division_indexes INTO index_name_value;
        IF done THEN
            LEAVE roster_index_loop;
        END IF;
        SET @migration_sql = CONCAT(
            'ALTER TABLE `duty_rosters` DROP INDEX `',
            REPLACE(index_name_value, '`', '``'),
            '`'
        );
        PREPARE migration_statement FROM @migration_sql;
        EXECUTE migration_statement;
        DEALLOCATE PREPARE migration_statement;
    END LOOP;
    CLOSE roster_division_indexes;

    SELECT COUNT(*) INTO has_roster_division
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'duty_rosters'
      AND column_name = 'division_id';

    IF has_roster_division > 0 THEN
        ALTER TABLE duty_rosters DROP COLUMN division_id;
    END IF;

    SELECT COUNT(*) INTO has_roster_unique_year
    FROM (
        SELECT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_rosters'
          AND non_unique = 0
        GROUP BY index_name
        HAVING COUNT(*) = 1 AND MAX(column_name) = 'academic_year'
    ) AS roster_year_indexes;

    IF has_roster_unique_year = 0 AND has_academic_year > 0 THEN
        ALTER TABLE duty_rosters
            MODIFY COLUMN academic_year VARCHAR(30) NOT NULL,
            ADD UNIQUE KEY uq_duty_rosters_academic_year (academic_year);
    ELSEIF has_academic_year > 0 THEN
        ALTER TABLE duty_rosters
            MODIFY COLUMN academic_year VARCHAR(30) NOT NULL;
    END IF;
END$$
DELIMITER ;

CALL migrate_duty_roster_global_areas();
DROP PROCEDURE migrate_duty_roster_global_areas;
