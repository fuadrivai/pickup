DROP PROCEDURE IF EXISTS migrate_duty_events_global;
DELIMITER $$
CREATE PROCEDURE migrate_duty_events_global()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE foreign_key_name VARCHAR(64);
    DECLARE index_name_value VARCHAR(64);
    DECLARE has_division_column INT DEFAULT 0;
    DECLARE has_global_unique_name INT DEFAULT 0;

    DECLARE foreign_keys CURSOR FOR
        SELECT DISTINCT constraint_name
        FROM information_schema.key_column_usage
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_events'
          AND column_name = 'division_id'
          AND referenced_table_name IS NOT NULL;

    DECLARE division_indexes CURSOR FOR
        SELECT DISTINCT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_events'
          AND column_name = 'division_id'
          AND index_name <> 'PRIMARY';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    DROP TEMPORARY TABLE IF EXISTS duty_event_merge_map;
    CREATE TEMPORARY TABLE duty_event_merge_map (
        source_id INT UNSIGNED NOT NULL PRIMARY KEY,
        target_id INT UNSIGNED NOT NULL
    );

    INSERT INTO duty_event_merge_map (source_id, target_id)
    SELECT source_event.id, MIN(target_event.id)
    FROM duty_events source_event
    JOIN duty_events target_event ON target_event.name = source_event.name
    GROUP BY source_event.id;

    UPDATE duty_roster_slots slot
    JOIN duty_event_merge_map mapping ON mapping.source_id = slot.event_id
    SET slot.event_id = mapping.target_id
    WHERE mapping.source_id <> mapping.target_id;

    DELETE duplicate_event
    FROM duty_events duplicate_event
    JOIN duty_event_merge_map mapping ON mapping.source_id = duplicate_event.id
    WHERE mapping.source_id <> mapping.target_id;

    OPEN foreign_keys;
    foreign_key_loop: LOOP
        FETCH foreign_keys INTO foreign_key_name;
        IF done THEN
            LEAVE foreign_key_loop;
        END IF;
        SET @migration_sql = CONCAT(
            'ALTER TABLE `duty_events` DROP FOREIGN KEY `',
            REPLACE(foreign_key_name, '`', '``'),
            '`'
        );
        PREPARE migration_statement FROM @migration_sql;
        EXECUTE migration_statement;
        DEALLOCATE PREPARE migration_statement;
    END LOOP;
    CLOSE foreign_keys;

    SET done = 0;
    OPEN division_indexes;
    index_loop: LOOP
        FETCH division_indexes INTO index_name_value;
        IF done THEN
            LEAVE index_loop;
        END IF;
        SET @migration_sql = CONCAT(
            'ALTER TABLE `duty_events` DROP INDEX `',
            REPLACE(index_name_value, '`', '``'),
            '`'
        );
        PREPARE migration_statement FROM @migration_sql;
        EXECUTE migration_statement;
        DEALLOCATE PREPARE migration_statement;
    END LOOP;
    CLOSE division_indexes;

    SELECT COUNT(*) INTO has_division_column
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'duty_events'
      AND column_name = 'division_id';

    IF has_division_column > 0 THEN
        ALTER TABLE duty_events DROP COLUMN division_id;
    END IF;

    SELECT COUNT(*) INTO has_global_unique_name
    FROM (
        SELECT index_name
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'duty_events'
          AND non_unique = 0
        GROUP BY index_name
        HAVING COUNT(*) = 1 AND MAX(column_name) = 'name'
    ) AS global_name_indexes;

    IF has_global_unique_name = 0 THEN
        ALTER TABLE duty_events ADD UNIQUE KEY uq_duty_events_name (name);
    END IF;

    DROP TEMPORARY TABLE duty_event_merge_map;
END$$
DELIMITER ;

CALL migrate_duty_events_global();
DROP PROCEDURE migrate_duty_events_global;
