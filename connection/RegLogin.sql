CREATE DATABASE voidx;

USE voidx;

CREATE TABLE reg_form (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    gender VARCHAR(10),
    cnad VARCHAR(20),
    address VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    pass VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(255)
);

CREATE TABLE friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,  
    receiver_id INT,  
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES reg_form(id) ON DELETE CASCADE,  
    FOREIGN KEY (receiver_id) REFERENCES reg_form(id) ON DELETE CASCADE  
);


-- Create the friendships table with the new constraints
CREATE TABLE friendships (
    id INT AUTO_INCREMENT PRIMARY KEY,          
    user_id INT,                                 
    friend_id INT,                               
    friendship_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  
    least_id INT GENERATED ALWAYS AS (LEAST(user_id, friend_id)) STORED,  
    greatest_id INT GENERATED ALWAYS AS (GREATEST(user_id, friend_id)) STORED,  
    FOREIGN KEY (user_id) REFERENCES reg_form(id) ON DELETE CASCADE,      
    FOREIGN KEY (friend_id) REFERENCES reg_form(id) ON DELETE CASCADE,     
    UNIQUE KEY unique_friendship (least_id, greatest_id)  
);

-- Create posts table to store the posts
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    post_content TEXT,
    post_type ENUM('public', 'friend') DEFAULT 'public',  
    post_caption VARCHAR(255),
    post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES reg_form(id) ON DELETE CASCADE
);

-- Create post_media table to store images and videos associated with a post
CREATE TABLE post_media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT,
    media_type ENUM('image', 'video'),  
    media_path VARCHAR(255),  
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

-- Create an index to improve queries for post visibility
CREATE INDEX idx_post_visibility ON posts (post_type);




-- Likes Table
CREATE TABLE likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    post_id INT,
    FOREIGN KEY (user_id) REFERENCES reg_form(id),
    FOREIGN KEY (post_id) REFERENCES posts(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Comments Table
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    post_id INT,
    comment_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES reg_form(id),
    FOREIGN KEY (post_id) REFERENCES posts(id)
);

-- Admin
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,      
    email VARCHAR(255) UNIQUE NOT NULL,     
    password VARCHAR(255) NOT NULL,         
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
);

-- Admin Activity Log Table
CREATE TABLE admin_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,          
    admin_id INT NOT NULL,                      
    action_type ENUM('create_admin', 'delete_user', 'delete_admin') NOT NULL, 
    target_table ENUM('reg_form', 'admin') NOT NULL, 
    target_id INT,                              
    action_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE 
);

ALTER TABLE friend_requests ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

