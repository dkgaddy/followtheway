-- The Way — followtheway.org
-- Database schema (MySQL 5.7+)
-- Run via phpMyAdmin or: mysql -u USER -p DBNAME < schema.sql

CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('member','admin') NOT NULL DEFAULT 'member',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sermons (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  title      VARCHAR(255) NOT NULL,
  speaker    VARCHAR(120) NOT NULL,
  date       DATE NOT NULL,
  mp3_url    TEXT NOT NULL,
  image_url  TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE thoughts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  body         LONGTEXT NOT NULL,
  author       VARCHAR(120) NOT NULL,
  image_url    TEXT NOT NULL DEFAULT '',
  publish_date DATE NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE events (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(255) NOT NULL,
  image_url   TEXT NOT NULL,
  event_date  DATE NOT NULL,
  description TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
