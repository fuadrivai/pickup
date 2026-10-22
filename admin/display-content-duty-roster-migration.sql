ALTER TABLE display_contents
    MODIFY COLUMN display_type ENUM(
        'video',
        'link',
        'slide',
        'schedule',
        'duty_roster',
        'api_events',
        'embed_code'
    ) NOT NULL;
