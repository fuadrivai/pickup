DROP PROCEDURE IF EXISTS migrate_duty_teachers_photo_path;
DELIMITER $$
CREATE PROCEDURE migrate_duty_teachers_photo_path()
BEGIN
    DECLARE has_photo_url INT DEFAULT 0;
    DECLARE has_photo_path INT DEFAULT 0;

    SELECT COUNT(*) INTO has_photo_url
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'duty_teachers'
      AND column_name = 'photo_url';

    SELECT COUNT(*) INTO has_photo_path
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'duty_teachers'
      AND column_name = 'photo_path';

    IF has_photo_url > 0 AND has_photo_path = 0 THEN
        ALTER TABLE duty_teachers
            CHANGE COLUMN photo_url photo_path VARCHAR(2048) DEFAULT NULL;
    ELSEIF has_photo_url > 0 AND has_photo_path > 0 THEN
        UPDATE duty_teachers
        SET photo_path = photo_url
        WHERE photo_path IS NULL AND photo_url IS NOT NULL;

        ALTER TABLE duty_teachers
            DROP COLUMN photo_url,
            MODIFY COLUMN photo_path VARCHAR(2048) DEFAULT NULL;
    ELSEIF has_photo_path > 0 THEN
        ALTER TABLE duty_teachers
            MODIFY COLUMN photo_path VARCHAR(2048) DEFAULT NULL;
    ELSE
        ALTER TABLE duty_teachers
            ADD COLUMN photo_path VARCHAR(2048) DEFAULT NULL;
    END IF;
END$$
DELIMITER ;

CALL migrate_duty_teachers_photo_path();
DROP PROCEDURE migrate_duty_teachers_photo_path;
