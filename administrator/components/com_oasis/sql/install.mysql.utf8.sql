CREATE TABLE IF NOT EXISTS `#__oasis_order`
(
    `order_id`       INT(11)        NOT NULL,
    `queue_id`       INT(11)        NOT NULL,
    PRIMARY KEY (`order_id`)
    )
    ENGINE = MyISAM
    DEFAULT CHARSET = utf8
    DEFAULT COLLATE = utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__oasis_product`
(
    `product_id_oasis`      CHAR(12)        NOT NULL,
    `group_id`              CHAR(12)        NOT NULL,
    `color_group_id`        CHAR(12)        NOT NULL,
    `rating`                TINYINT(1)      NOT NULL,
    `option_date_modified`  DATETIME        NOT NULL,
    `product_id`            INT(11)         NOT NULL,
    `article`               CHAR(12)        NOT NULL,
    PRIMARY KEY (`product_id_oasis`)
    )
    ENGINE = MyISAM
    DEFAULT CHARSET = utf8
    DEFAULT COLLATE = utf8_general_ci;

CREATE TABLE IF NOT EXISTS `#__oasis_categories`
(
    `category_id_oasis`     CHAR(12)        NOT NULL,
    `category_id`           CHAR(12)        NOT NULL,
    PRIMARY KEY (`category_id_oasis`)
    )
    ENGINE = MyISAM
    DEFAULT CHARSET = utf8
    DEFAULT COLLATE = utf8_general_ci;
