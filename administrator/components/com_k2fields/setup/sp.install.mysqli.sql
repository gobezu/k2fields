DELIMITER //
CREATE PROCEDURE `#__k2_extra_fields_geodist`(IN `itemId` INT, IN `latIn` DOUBLE, IN `lngIn` DOUBLE, IN `fieldId` INT, IN `dist` INT, IN `lim` INT)
BEGIN
        DECLARE mylng DOUBLE;
        DECLARE mylat DOUBLE;
        DECLARE lng1 FLOAT;
        DECLARE lng2 FLOAT;
        DECLARE lat1 FLOAT;
        DECLARE lat2 FLOAT;
        DECLARE q VARCHAR(255);

        IF itemId > 0 THEN
                SELECT lat, lng INTO mylat, mylng
                FROM jos_k2_extra_fields_values
                WHERE
                        itemid = @itemId AND
                        fieldid = @fieldId AND
                        (lat IS NOT NULL AND lat <> "" AND lng IS NOT NULL AND lng <> "")
                LIMIT 1;
        ELSE
                SET mylat = latIn;
                SET mylng = lngIn;
        END IF;

        IF mylat IS NULL OR mylat = '' OR mylng IS NULL OR mylng = '' THEN
                SELECT NULL;
        ELSE
                IF lim <= 0 OR lim IS NULL OR lim = '' THEN SET lim = 20; END IF;

                -- calculate lon and lat for the rectangle:
                SET lng1 = mylng - dist / ABS(cos(radians(mylat)) * 69);
                SET lng2 = mylng + dist / ABS(cos(radians(mylat)) * 69);
                SET lat1 = mylat - (dist / 69);
                SET lat2 = mylat + (dist / 69);

                -- run the query:

                PREPARE STMT FROM 'SELECT DISTINCT itemid FROM (SELECT DISTINCT
                        dest.itemid,
                        3956 * 2 *
                        ASIN(
                                SQRT(
                                        POWER(SIN((orig.lat - dest.lat) * pi() / 180 / 2), 2) +
                                        COS(orig.lat * PI() / 180) *  COS(dest.lat * pi() / 180) *
                                        POWER(SIN((orig.lng - dest.lng) * pi()/180 / 2), 2)
                                )
                        ) AS distance
                FROM
                        jos_k2_extra_fields_values dest,
                        jos_k2_extra_fields_values orig
                WHERE
                        orig.itemid = ? AND
                        orig.fieldid = ? AND
                        (orig.lat IS NOT NULL AND orig.lat <> "" AND orig.lng IS NOT NULL AND orig.lng <> "") AND
                        dest.lng BETWEEN ? AND ? AND
                        dest.lat BETWEEN ? AND ?
                ) AS s
                WHERE
                        distance < @dist
                ORDER BY
                        distance
                LIMIT ?';

                EXECUTE STMT USING @itemId, @fieldId, @lng1, @lng2, @lat1, @lat2, @lim;
        END IF;
END;

CREATE PROCEDURE `#__k2_extra_fields_copy`(IN `copyField` INT, IN `toGroup` INT, IN `newName` VARCHAR(100))
BEGIN
        declare newFieldId int;

        insert into #__k2_extra_fields(name, value, `type`, `group`, published, ordering)
                select concat(newName, substr(name, length(substr(name, 1, instr(name, ' in '))))), value, 'textfield', toGroup, 1, (select max(ordering) + 1 from #__k2_extra_fields where `group` = toGroup)
                from #__k2_extra_fields
                where id = copyField;

        set newFieldId = LAST_INSERT_ID();

        insert into #__k2_extra_fields_definition(id, definition)
                select newFieldId, concat(substr(definition, 1, length(definition) - instr(reverse(definition), '---') + 1), newName)
                from #__k2_extra_fields_definition
                where id = copyField;
END;

