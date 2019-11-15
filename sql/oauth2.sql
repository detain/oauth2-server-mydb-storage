SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `oauth_access_token_scopes`;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
DROP TABLE IF EXISTS `oauth_access_tokens`;
DROP TABLE IF EXISTS `oauth_auth_code_scopes`;
DROP TABLE IF EXISTS `oauth_auth_codes`;
DROP TABLE IF EXISTS `oauth_client_redirect_uris`;
DROP TABLE IF EXISTS `oauth_clients`;
DROP TABLE IF EXISTS `oauth_scopes`;
DROP TABLE IF EXISTS `oauth_session_scopes`;
DROP TABLE IF EXISTS `oauth_sessions`;

CREATE TABLE `oauth_access_token_scopes` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`access_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	KEY `access_token` (`access_token`),
	KEY `scope` (`scope`),
	CONSTRAINT `oauth_access_token_scopes_ibfk_1` FOREIGN KEY (`access_token`) REFERENCES `oauth_access_tokens` (`access_token`) ON DELETE CASCADE,
	CONSTRAINT `oauth_access_token_scopes_ibfk_2` FOREIGN KEY (`scope`) REFERENCES `oauth_scopes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_access_tokens` (
	`access_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Access Token',
	`session_id` int(11) unsigned NOT NULL COMMENT 'Session ID',
	`expire_time` int(11) unsigned NOT NULL COMMENT 'Expire Time',
	PRIMARY KEY (`access_token`),
	KEY `oauth_access_tokens_ibfk_1` (`session_id`),
	CONSTRAINT `oauth_access_tokens_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `oauth_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OAuth2 Access Tokens';

CREATE TABLE `oauth_auth_codes` (
	`auth_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`session_id` int(11) unsigned NOT NULL,
	`expire_time` int(11) unsigned NOT NULL,
	`client_redirect_uri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	PRIMARY KEY (`auth_code`),
	KEY `session_id` (`session_id`),
	CONSTRAINT `oauth_auth_codes_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `oauth_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_client_redirect_uris` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`client_id` int(11) unsigned NOT NULL COMMENT 'App Owner ID',
	`redirect_uri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Redirect URI',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_clients` (
	`id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Client App ID',
	`account_id` int(11) unsigned NOT NULL COMMENT 'App Creator',
	`name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Application Name',
	`secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Client App Secret',
	PRIMARY KEY (`id`),
	KEY `oauthclient_account_id_idx` (`account_id`),
	CONSTRAINT `oauthclient_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OAuth2 Client Apps';

CREATE TABLE `oauth_refresh_tokens` (
	`refresh_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`expire_time` int(11) unsigned NOT NULL,
	`access_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	PRIMARY KEY (`refresh_token`),
	KEY `access_token` (`access_token`),
	CONSTRAINT `oauth_refresh_tokens_ibfk_1` FOREIGN KEY (`access_token`) REFERENCES `oauth_access_tokens` (`access_token`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_scopes` (
	`id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Scope ID',
	`description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OAuth2 Scopes';

CREATE TABLE `oauth_session_scopes` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	`session_id` int(11) unsigned NOT NULL,
	`scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	PRIMARY KEY (`id`),
	KEY `session_id` (`session_id`),
	KEY `scope` (`scope`),
	CONSTRAINT `oauth_session_scopes_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `oauth_sessions` (`id`) ON DELETE CASCADE,
	CONSTRAINT `oauth_session_scopes_ibfk_2` FOREIGN KEY (`scope`) REFERENCES `oauth_scopes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_sessions` (
	`id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Session ID',
	`account_id` int(11) unsigned NOT NULL COMMENT 'End-User Account',
	`client_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'OAuth2 App Owner',
	`client_redirect_uri` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Redirection URI',
	PRIMARY KEY (`id`),
	KEY `oauthses_account_id_idx` (`account_id`),
	KEY `oauthses_client_id` (`client_id`),
	CONSTRAINT `oauthses_account_id` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE ON UPDATE CASCADE,
	CONSTRAINT `oauthses_client_id` FOREIGN KEY (`client_id`) REFERENCES `oauth_clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OAuth2 Sessions';

SET FOREIGN_KEY_CHECKS=1;

-- mysqldump my --tables $(echo 'show tables;'|mysql -s my|grep oau)|grep "^DROP TABLE"
-- mysqldump my --tables $(echo 'show tables;'|mysql -s my|grep oau)|grep -v -e "^--" -e "^/\*" -e "^LOCK " -e "^UNLOCK " -e "^DROP " -e "^$"|sed s#"^CREATE TABLE "#"\nCREATE TABLE "#g
INSERT INTO oauth_scopes VALUES('basic','Basic details about your account');
INSERT INTO oauth_scopes VALUES('email','Your email address');
INSERT INTO oauth_scopes VALUES('photo','Your photo');
INSERT INTO oauth_clients VALUES('rocketchat_2773', 2773, 'secret', 'Test Client');
-- CREATE TABLE 'users' ('id' int(11) unsigned not null primary key auto_increment, 'username' varchar(255) not null, 'password' varchar(255) not null, 'name' varchar(255) not null, 'email' varchar(255) not null, 'photo' varchar(255) not null);
-- INSERT INTO 'users VALUES(1,'alexbilbie','$2y$10$hZEDpwByBA05/ZTQaQozZe/inW.IcFhZhbpXOSyQbiOd04vWLujNG','Alex Bilbie','hello@alexbilbie.com','https://s.gravatar.com/avatar/14902eb1dac66b8458ebbb481d80f0a3');
-- INSERT INTO 'users VALUES(2,'philsturgeon','$2y$10$473th0UCj3v3DukSFVOA4eIuKM5vBqE.rds8JxpXUn1HW9it1a7GW','Phil Sturgeon','email@philsturgeon.co.uk','https://s.gravatar.com/avatar/14df293d6c5cd6f05996dfc606a6a951');
-- INSERT INTO oauth_sessions VALUES(1,'client','testclient','testclient','');
-- INSERT INTO oauth_sessions VALUES(2,'user',1,'testclient','');
-- INSERT INTO oauth_sessions VALUES(3,'user',2,'testclient','');
-- INSERT INTO oauth_access_tokens VALUES('iamgod',1,1458207696);
-- INSERT INTO oauth_access_tokens VALUES('iamalex',2,1458207696);
-- INSERT INTO oauth_access_tokens VALUES('iamphil',3,1458207696);
-- INSERT INTO oauth_access_token_scopes VALUES(1,'iamgod','basic');
-- INSERT INTO oauth_access_token_scopes VALUES(2,'iamgod','email');
-- INSERT INTO oauth_access_token_scopes VALUES(3,'iamgod','photo');
-- INSERT INTO oauth_access_token_scopes VALUES(4,'iamphil','email');
-- INSERT INTO oauth_access_token_scopes VALUES(5,'iamalex','photo');
-- INSERT INTO oauth_client_redirect_uris VALUES(1,'testclient','http://example.com/redirect');
