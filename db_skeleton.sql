CREATE TABLE IF NOT EXISTS users
(
    uid VARCHAR(60) PRIMARY KEY,
    username varchar(60) default null,
    name varchar(240) not null,
    email varchar(60) unique not null,
    phone varchar(24) default null,
    admin bool default false,
    active bool default false,
    token varchar(60) default null,
    token_expiry timestamp without time zone default null,
    created_at timestamp without time zone NOT NULL DEFAULT now()

    ); -- this is actually ldap

CREATE TABLE IF NOT EXISTS clarion_users
(
    id    int primary key,
    email VARCHAR(60) UNIQUE NOT NULL
    );

CREATE TABLE IF NOT EXISTS conferences
(
    slug VARCHAR(60) PRIMARY KEY,
    title VARCHAR(240) NOT NULL,
    description TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    location VARCHAR(240) NOT NULL,
    registration_open DATE NOT NULL,
    registration_close DATE NOT NULL
    );

CREATE TABLE IF NOT EXISTS teams
(
    conference  VARCHAR(60),
    slug        VARCHAR(60),
    name        VARCHAR(240),
    description TEXT,
    PRIMARY KEY (conference, slug),
    FOREIGN KEY (conference) REFERENCES conferences (slug)
    );

CREATE TABLE IF NOT EXISTS volunteers
(
    id            serial PRIMARY KEY ,
    name          VARCHAR(240) NOT NULL,
    clarion_email VARCHAR(60) NULL, -- not all users are clarion users
    "user"          VARCHAR(60) NULL, -- this may be a legacy volunteer from clarion
    lang varchar(6) not null,
    mugshot      varchar(192)        NULL, -- could be a URL
    tshirt_size varchar(6) not null,
    tshirt_cut varchar(6) not null,
    food_preferences varchar(24) not null,
    previous_experience text null,
    notes        text            NULL,
    verified bool default false not null,
    registration_date timestamp without time zone NOT NULL DEFAULT now(),
    FOREIGN KEY (clarion_email) REFERENCES clarion_users (email),
    FOREIGN KEY ("user") REFERENCES users (uid)
    );

CREATE TABLE IF NOT EXISTS volunteer_teams
(
    volunteer  INT,
    team       VARCHAR(60),
    is_primary INT, -- boolean, may have more than one, or none
    PRIMARY KEY (volunteer, conference, team),
    FOREIGN KEY (volunteer) REFERENCES volunteers (id),
    FOREIGN KEY (conference, team) REFERENCES teams (conference, slug),
    FOREIGN KEY (conference) REFERENCES conferences (slug)
    );
