BEGIN TRANSACTION;
CREATE TABLE "oauth_clients" (
  "id"     VARCHAR NOT NULL,
  "secret" VARCHAR NOT NULL,
  "name"   VARCHAR NOT NULL,
  PRIMARY KEY ("id")
);
--INSERT INTO `oauth_clients` VALUES('testclient','secret','Test Client');

CREATE TABLE "oauth_client_redirect_uris" (
  "id"           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "client_id"    VARCHAR NOT NULL,
  "redirect_uri" VARCHAR NOT NULL
);
--CREATE TABLE "users" ("id" integer not null primary key autoincrement, "username" varchar not null, "password" varchar not null, "name" varchar not null, "email" varchar not null, "photo" varchar not null);
--INSERT INTO `users` VALUES(1,'alexbilbie','$2y$10$hZEDpwByBA05/ZTQaQozZe/inW.IcFhZhbpXOSyQbiOd04vWLujNG','Alex Bilbie','hello@alexbilbie.com','https://s.gravatar.com/avatar/14902eb1dac66b8458ebbb481d80f0a3');
--INSERT INTO `users` VALUES(2,'philsturgeon','$2y$10$473th0UCj3v3DukSFVOA4eIuKM5vBqE.rds8JxpXUn1HW9it1a7GW','Phil Sturgeon','email@philsturgeon.co.uk','https://s.gravatar.com/avatar/14df293d6c5cd6f05996dfc606a6a951');

CREATE TABLE "oauth_scopes" (
  "id"          VARCHAR NOT NULL,
  "description" VARCHAR NOT NULL,
  PRIMARY KEY ("id")
);
--INSERT INTO `oauth_scopes` VALUES('basic','Basic details about your account');
--INSERT INTO `oauth_scopes` VALUES('email','Your email address');
--INSERT INTO `oauth_scopes` VALUES('photo','Your photo');

CREATE TABLE "oauth_sessions" (
  "id"                  INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "owner_type"          VARCHAR NOT NULL,
  "owner_id"            VARCHAR NOT NULL,
  "client_id"           VARCHAR NOT NULL,
  "client_redirect_uri" VARCHAR NULL,
  FOREIGN KEY ("client_id") REFERENCES "oauth_clients" ("id")
    ON DELETE CASCADE
);
--INSERT INTO `oauth_sessions` VALUES(1,'client','testclient','testclient','');
--INSERT INTO `oauth_sessions` VALUES(2,'user',1,'testclient','');
--INSERT INTO `oauth_sessions` VALUES(3,'user',2,'testclient','');

CREATE TABLE "oauth_session_scopes" (
  "id"         INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "session_id" INTEGER NOT NULL,
  "scope"      VARCHAR NOT NULL,
  FOREIGN KEY ("session_id") REFERENCES "oauth_sessions" ("id")
    ON DELETE CASCADE,
  FOREIGN KEY ("scope") REFERENCES "oauth_scopes" ("id")
    ON DELETE CASCADE
);
CREATE TABLE "oauth_access_tokens" (
  "access_token" VARCHAR NOT NULL,
  "session_id"   INTEGER NOT NULL,
  "expire_time"  INTEGER NOT NULL,
  FOREIGN KEY ("session_id") REFERENCES "oauth_sessions" ("id")
    ON DELETE CASCADE,
  PRIMARY KEY ("access_token")
);
--INSERT INTO `oauth_access_tokens` VALUES('iamgod',1,1458207696);
--INSERT INTO `oauth_access_tokens` VALUES('iamalex',2,1458207696);
--INSERT INTO `oauth_access_tokens` VALUES('iamphil',3,1458207696);

CREATE TABLE "oauth_access_token_scopes" (
  "id"           INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "access_token" VARCHAR NOT NULL,
  "scope"        VARCHAR NOT NULL,
  FOREIGN KEY ("access_token") REFERENCES "oauth_access_tokens" ("access_token")
    ON DELETE CASCADE,
  FOREIGN KEY ("scope") REFERENCES "oauth_scopes" ("id")
    ON DELETE CASCADE
);
--INSERT INTO `oauth_access_token_scopes` VALUES(1,'iamgod','basic');
--INSERT INTO `oauth_access_token_scopes` VALUES(2,'iamgod','email');
--INSERT INTO `oauth_access_token_scopes` VALUES(3,'iamgod','photo');
--INSERT INTO `oauth_access_token_scopes` VALUES(4,'iamphil','email');
--INSERT INTO `oauth_access_token_scopes` VALUES(5,'iamalex','photo');

CREATE TABLE "oauth_refresh_tokens" (
  "refresh_token" VARCHAR NOT NULL,
  "expire_time"   INTEGER NOT NULL,
  "access_token"  VARCHAR NOT NULL,
  FOREIGN KEY ("access_token") REFERENCES "oauth_access_tokens" ("access_token")
    ON DELETE CASCADE,
  PRIMARY KEY ("refresh_token")
);
--INSERT INTO `oauth_client_redirect_uris` VALUES(1,'testclient','http://example.com/redirect');

CREATE TABLE "oauth_auth_codes" (
  "auth_code"           VARCHAR NOT NULL,
  "session_id"          INTEGER NOT NULL,
  "expire_time"         INTEGER NOT NULL,
  "client_redirect_uri" VARCHAR NOT NULL,
  FOREIGN KEY ("session_id") REFERENCES "oauth_sessions" ("id")
    ON DELETE CASCADE,
  PRIMARY KEY ("auth_code")
);
CREATE TABLE "oauth_auth_code_scopes" (
  "id"        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "auth_code" VARCHAR NOT NULL,
  "scope"     VARCHAR NOT NULL,
  FOREIGN KEY ("auth_code") REFERENCES "oauth_auth_codes" ("auth_code")
    ON DELETE CASCADE,
  FOREIGN KEY ("scope") REFERENCES "oauth_scopes" ("id")
    ON DELETE CASCADE
);

COMMIT;
