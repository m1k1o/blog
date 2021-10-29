CREATE TABLE images (
  "id" serial PRIMARY KEY,
  "name" varchar(255) NOT NULL,
  "path" varchar(255) DEFAULT NULL,
  "thumb" varchar(255) DEFAULT NULL,
  "type" varchar(10) NOT NULL,
  "md5" char(32) NOT NULL,
  "datetime" timestamp NOT NULL,
  "status" int NOT NULL
);

CREATE TYPE privacy_t as enum('private','friends','public');

CREATE TABLE posts (
  "id" serial PRIMARY KEY,
  "text" text NOT NULL,
  "plain_text" text NOT NULL,
  "feeling" varchar(255) NOT NULL,
  "persons" varchar(255) NOT NULL,
  "location" varchar(255) NOT NULL,
  "content" varchar(1000) NOT NULL,
  "content_type" varchar(255) NOT NULL,
  "privacy" privacy_t NOT NULL,
  "datetime" timestamp NOT NULL,
  "status" int NOT NULL
);
