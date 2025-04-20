CREATE TABLE user (
    id CHAR(36) PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE image (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(1024) NOT NULL
);

CREATE TABLE painting (
    id CHAR(36) PRIMARY KEY,
    title VARCHAR(255),
    price DECIMAL(10, 2),
    is_sold BOOLEAN DEFAULT FALSE,
    description TEXT,
    size_v FLOAT,
    size_h FLOAT,
    finished_at DATE
);

CREATE TABLE collection (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(255),
    description TEXT,
    position INT,
    started_at DATE,
    finished_at DATE
);

CREATE TABLE exhibition (
    id CHAR(36) PRIMARY KEY,
    location VARCHAR(255),
    description TEXT,
    position INT,
    started_at DATE,
    finished_at DATE
);

CREATE TABLE painting_image (
    image_id CHAR(36),
    painting_id CHAR(36),
    position INT,
    PRIMARY KEY (image_id, painting_id),
    FOREIGN KEY (image_id) REFERENCES image(id) ON DELETE CASCADE,
    FOREIGN KEY (painting_id) REFERENCES painting(id) ON DELETE CASCADE
);

CREATE TABLE exhibition_image (
    image_id CHAR(36),
    exhibition_id CHAR(36),
    position INT,
    PRIMARY KEY (image_id, exhibition_id),
    FOREIGN KEY (image_id) REFERENCES image(id) ON DELETE CASCADE,
    FOREIGN KEY (exhibition_id) REFERENCES exhibition(id) ON DELETE CASCADE
);

CREATE TABLE collection_painting (
    painting_id CHAR(36),
    collection_id CHAR(36),
    position INT,
    PRIMARY KEY (painting_id, collection_id),
    FOREIGN KEY (painting_id) REFERENCES painting(id) ON DELETE CASCADE,
    FOREIGN KEY (collection_id) REFERENCES collection(id) ON DELETE CASCADE
);