CREATE TABLE IF NOT EXISTS duty_divisions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_duty_divisions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_teachers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    photo_path VARCHAR(2048) DEFAULT NULL,
    division_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (id),
    KEY idx_duty_teachers_division (division_id),
    CONSTRAINT fk_duty_teachers_division
        FOREIGN KEY (division_id) REFERENCES duty_divisions (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_places (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_duty_places_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_events (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_duty_events_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_rosters (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    academic_year VARCHAR(30) NOT NULL,
    notes TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_duty_rosters_academic_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_roster_slots (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    roster_id INT UNSIGNED NOT NULL,
    section_title VARCHAR(150) DEFAULT NULL,
    assignment_title VARCHAR(150) DEFAULT NULL,
    place_id INT UNSIGNED DEFAULT NULL,
    event_id INT UNSIGNED DEFAULT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_duty_roster_slots_roster (roster_id, sort_order, start_time),
    KEY idx_duty_roster_slots_place (place_id),
    KEY idx_duty_roster_slots_event (event_id),
    CONSTRAINT fk_duty_roster_slots_roster
        FOREIGN KEY (roster_id) REFERENCES duty_rosters (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_duty_roster_slots_place
        FOREIGN KEY (place_id) REFERENCES duty_places (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_duty_roster_slots_event
        FOREIGN KEY (event_id) REFERENCES duty_events (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_roster_assignments (
    slot_id INT UNSIGNED NOT NULL,
    weekday TINYINT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (slot_id, weekday, teacher_id),
    KEY idx_duty_roster_assignments_teacher (teacher_id),
    CONSTRAINT fk_duty_roster_assignments_slot
        FOREIGN KEY (slot_id) REFERENCES duty_roster_slots (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_duty_roster_assignments_teacher
        FOREIGN KEY (teacher_id) REFERENCES duty_teachers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO duty_divisions (name) VALUES
    ('Preschool'),
    ('Primary'),
    ('Secondary'),
    ('Development Class'),
    ('Operasional')
ON DUPLICATE KEY UPDATE name = VALUES(name);
