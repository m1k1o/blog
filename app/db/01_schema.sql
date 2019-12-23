CREATE TABLE `images` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) DEFAULT NULL,
  `thumb` varchar(255) DEFAULT NULL,
  `type` varchar(10) NOT NULL,
  `md5` char(32) NOT NULL,
  `datetime` datetime NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `text` longtext NOT NULL,
  `plain_text` longtext NOT NULL,
  `feeling` varchar(255) NOT NULL,
  `persons` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `content` varchar(1000) NOT NULL,
  `content_type` varchar(255) NOT NULL,
  `privacy` set('private','friends','public') NOT NULL,
  `datetime` datetime NOT NULL,
  `status` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


ALTER TABLE `images`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;