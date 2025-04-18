CREATE TABLE Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Paintings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2),
    description TEXT,
    size_v FLOAT,
    size_h FLOAT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    position INT,
    started_at DATETIME NOT NULL,
    ended_at DATETIME
);

CREATE TABLE CollectionPaintings (
    painting_id INT NOT NULL,
    collection_id INT NOT NULL,
    position INT,
    PRIMARY KEY (painting_id, collection_id),
    FOREIGN KEY (painting_id) REFERENCES Paintings(id) ON DELETE CASCADE,
    FOREIGN KEY (collection_id) REFERENCES Collections(id) ON DELETE CASCADE
);
