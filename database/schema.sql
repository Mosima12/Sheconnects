-- =====================================================
-- She Connects — Women's Empowerment Platform

-- =====================================================

CREATE DATABASE IF NOT EXISTS she_connects
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE she_connects;

-- ---------------------------------------------------
-- users
-- ---------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100)     NOT NULL,
  email         VARCHAR(190)     NOT NULL,
  password_hash VARCHAR(255)     NOT NULL,
  bio           TEXT             NULL,
  skills        VARCHAR(500)     NULL,
  interests     VARCHAR(500)     NULL,
  career_goals  VARCHAR(500)     NULL,
  industry      VARCHAR(120)     NULL,
  is_mentor     TINYINT(1)       NOT NULL DEFAULT 0,
  created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- resources
-- status: 'available' | 'reserved' | 'given'
-- exchange_type: 'give' | 'lend' | 'exchange'
-- ---------------------------------------------------
CREATE TABLE resources (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  title         VARCHAR(150)     NOT NULL,
  description   TEXT             NOT NULL,
  category      VARCHAR(60)      NOT NULL,
  exchange_type ENUM('give','lend','exchange') NOT NULL DEFAULT 'give',
  location      VARCHAR(120)     NULL,
  status        ENUM('available','reserved','given') NOT NULL DEFAULT 'available',
  created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_resources_category (category),
  KEY idx_resources_status (status),
  KEY idx_resources_user (user_id),
  CONSTRAINT fk_resources_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- resource_requests
-- status: 'pending' | 'accepted' | 'declined' | 'cancelled'
-- ---------------------------------------------------
CREATE TABLE resource_requests (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  resource_id  INT UNSIGNED NOT NULL,
  requester_id INT UNSIGNED NOT NULL,
  message      TEXT         NOT NULL,
  status       ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rr_resource (resource_id),
  KEY idx_rr_requester (requester_id),
  KEY idx_rr_status (status),
  CONSTRAINT fk_rr_resource
    FOREIGN KEY (resource_id) REFERENCES resources(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_rr_requester
    FOREIGN KEY (requester_id) REFERENCES users(id)
    ON DELETE CASCADE,
  UNIQUE KEY uq_rr_pending (resource_id, requester_id, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- connections
-- status: 'pending' | 'accepted' | 'declined' | 'cancelled'
-- ---------------------------------------------------
CREATE TABLE connections (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender_id   INT UNSIGNED NOT NULL,
  receiver_id INT UNSIGNED NOT NULL,
  message     TEXT         NOT NULL,
  status      ENUM('pending','accepted','declined','cancelled') NOT NULL DEFAULT 'pending',
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_conn_sender (sender_id),
  KEY idx_conn_receiver (receiver_id),
  KEY idx_conn_status (status),
  CONSTRAINT fk_conn_sender
    FOREIGN KEY (sender_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_conn_receiver
    FOREIGN KEY (receiver_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- opportunities
-- ---------------------------------------------------
CREATE TABLE opportunities (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED NOT NULL,
  title           VARCHAR(150) NOT NULL,
  description     TEXT         NOT NULL,
  category        VARCHAR(60)  NOT NULL,
  organization    VARCHAR(150) NOT NULL,
  application_url VARCHAR(500) NULL,
  deadline        DATE         NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_opp_category (category),
  KEY idx_opp_user (user_id),
  CONSTRAINT fk_opp_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------
-- saved_opportunities (optional)
-- ---------------------------------------------------
CREATE TABLE saved_opportunities (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED NOT NULL,
  opportunity_id INT UNSIGNED NOT NULL,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_saved (user_id, opportunity_id),
  CONSTRAINT fk_saved_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_saved_opp
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;