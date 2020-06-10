CREATE TABLE `images` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` TEXT NOT NULL,
  `path` TEXT DEFAULT NULL,
  `thumb` TEXT DEFAULT NULL,
  `type` TEXT NOT NULL,
  `md5` TEXT NOT NULL,
  `datetime` INTEGER NOT NULL,
  `status` INTEGER NOT NULL
);

CREATE TABLE `posts` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `text` TEXT NOT NULL,
  `plain_text` TEXT NOT NULL,
  `feeling` TEXT NOT NULL,
  `persons` TEXT NOT NULL,
  `location` TEXT NOT NULL,
  `content` TEXT NOT NULL,
  `content_type` TEXT NOT NULL,
  `privacy` TEXT NOT NULL,
  `datetime` INTEGER NOT NULL,
  `status` INTEGER NOT NULL
);
