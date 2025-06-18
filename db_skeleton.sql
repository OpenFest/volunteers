CREATE TABLE IF NOT EXISTS users
(
    uid VARCHAR(60) PRIMARY KEY,
    name varchar(240) not null,
    email varchar(60) unique not null,
    phone varchar(24) not null,
    active bool default false

    ); -- this is actually ldap

CREATE TABLE IF NOT EXISTS clarion_users
(
    id    int primary key,
    email VARCHAR(60) UNIQUE NOT NULL
    );

CREATE TABLE IF NOT EXISTS conferences
(
    slug VARCHAR(60) PRIMARY KEY
    );

CREATE TABLE IF NOT EXISTS teams
(
    conference  VARCHAR(60),
    slug        VARCHAR(60),
    description TEXT,
    PRIMARY KEY (conference, slug),
    FOREIGN KEY (conference) REFERENCES conferences (slug)
    );

CREATE TABLE IF NOT EXISTS volunteers
(
    id            serial PRIMARY KEY ,
    clarion_email VARCHAR(60) NULL, -- not all users are clarion users
    "user"          VARCHAR(60) NULL, -- this may be a legacy volunteer from clarion
    mugshot      bytea        NULL, -- could be a URL
    FOREIGN KEY (clarion_email) REFERENCES clarion_users (email),
    FOREIGN KEY ("user") REFERENCES users (uid)
    );

CREATE TABLE IF NOT EXISTS volunteer_teams
(
    volunteer  INT,
    conference VARCHAR(60),
    team       VARCHAR(60),
    is_primary INT, -- boolean, may have more than one, or none
    PRIMARY KEY (volunteer, conference, team),
    FOREIGN KEY (volunteer) REFERENCES volunteers (id),
    FOREIGN KEY (conference, team) REFERENCES teams (conference, slug),
    FOREIGN KEY (conference) REFERENCES conferences (slug)
    );
