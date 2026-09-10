-- ThriftVibe schema only.\r\n-- User records, password hashes, orders, uploads, and other sample data are intentionally excluded.\r\n\r\nCREATE TABLE users (
    userid INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15) NULL,
    profilephoto VARCHAR(255) DEFAULT 'default.jpg',
    role ENUM('member', 'seller', 'admin') DEFAULT 'member',
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE addresses (
    addressid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL,
    label VARCHAR(50) NOT NULL,
    recipientname VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    fulladdress TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    postalcode VARCHAR(10) NULL,
    isdefault BOOLEAN DEFAULT FALSE,
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE stores (
    storeid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL UNIQUE,
    storename VARCHAR(100) NOT NULL UNIQUE,
    city VARCHAR(50) NOT NULL,
    description TEXT NULL,
    membershiplevel ENUM('regular', 'premium') DEFAULT 'regular',
    totalrating DECIMAL(3,2) DEFAULT 0,
    totalreviews INT DEFAULT 0,
    totalfollowers INT DEFAULT 0,
    expireddate DATE NULL,
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE products (
    productid INT PRIMARY KEY AUTO_INCREMENT,
    storeid INT NOT NULL,
    productname VARCHAR(100) NOT NULL,
    category ENUM('Hoodie', 'Jaket', 'Kaos', 'Celana') NOT NULL,
    price INT NOT NULL CHECK (price >= 0),
    stock INT DEFAULT 1,
    description TEXT NULL,
    size VARCHAR(10) NULL,
    brand VARCHAR(50) NULL,
    videofile VARCHAR(255) NULL,
    thumbnail VARCHAR(255) NOT NULL,
    uploadedat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (storeid) REFERENCES stores(storeid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE carts (
    cartid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL,
    productid INT NOT NULL,
    quantity INT DEFAULT 1,
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart (userid, productid),
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE,
    FOREIGN KEY (productid) REFERENCES products(productid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE orders (
    orderid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL,
    totalamount INT NOT NULL,
    shippingaddress TEXT NOT NULL,
    courier VARCHAR(50) NOT NULL,
    paymentproof VARCHAR(255) NULL,
    trackingnumber VARCHAR(100) NULL,
    status ENUM('pending', 'packed', 'shipped', 'completed') DEFAULT 'pending',
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE orderdetails (
    detailid INT PRIMARY KEY AUTO_INCREMENT,
    orderid INT NOT NULL,
    productid INT NOT NULL,
    quantity INT NOT NULL,
    price INT NOT NULL,
    FOREIGN KEY (orderid) REFERENCES orders(orderid) ON DELETE CASCADE,
    FOREIGN KEY (productid) REFERENCES products(productid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE wishlists (
    wishlistid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL,
    productid INT NOT NULL,
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (userid, productid),
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE,
    FOREIGN KEY (productid) REFERENCES products(productid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE reviews (
    reviewid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL,
    storeid INT NOT NULL,
    orderid INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NULL,
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (userid, orderid),
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE,
    FOREIGN KEY (storeid) REFERENCES stores(storeid) ON DELETE CASCADE,
    FOREIGN KEY (orderid) REFERENCES orders(orderid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE follows (
    followid INT PRIMARY KEY AUTO_INCREMENT,
    userid INT NOT NULL,
    storeid INT NOT NULL,
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (userid, storeid),
    FOREIGN KEY (userid) REFERENCES users(userid) ON DELETE CASCADE,
    FOREIGN KEY (storeid) REFERENCES stores(storeid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE reports (
    reportid INT PRIMARY KEY AUTO_INCREMENT,
    reporterid INT NOT NULL,
    reportedtype ENUM('product', 'store') NOT NULL,
    reportedid INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporterid) REFERENCES users(userid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE statistics (
    statid INT PRIMARY KEY AUTO_INCREMENT,
    storeid INT NOT NULL,
    date DATE NOT NULL,
    views INT DEFAULT 0,
    productclicks INT DEFAULT 0,
    transactions INT DEFAULT 0,
    revenue INT DEFAULT 0,
    UNIQUE KEY unique_stat (storeid, date),
    FOREIGN KEY (storeid) REFERENCES stores(storeid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;;\r\n\r\nCREATE TABLE subscriptions (
    subscriptionid INT PRIMARY KEY AUTO_INCREMENT,
    storeid INT NOT NULL,
    package ENUM('monthly', 'yearly') NOT NULL,
    amount INT NOT NULL,
    transferproof VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'rejected', 'expired') DEFAULT 'pending',
    createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expireddate DATE NULL,
    FOREIGN KEY (storeid) REFERENCES stores(storeid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\r\n