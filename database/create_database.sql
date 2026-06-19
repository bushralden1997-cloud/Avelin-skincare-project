-- =====================================================
-- AVELIN Skincare — Complete Database
-- Safe: IF NOT EXISTS + INSERT IGNORE
-- =====================================================

CREATE DATABASE IF NOT EXISTS ciltbakim_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ciltbakim_db;

-- TABLES
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS brands (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    logo_url VARCHAR(255),
    country VARCHAR(100),
    founded_year INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) DEFAULT '',
    last_name VARCHAR(50) DEFAULT '',
    name VARCHAR(100) DEFAULT '',
    address TEXT,
    phone VARCHAR(20),
    role ENUM('user','admin') DEFAULT 'user',
    registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    brand_id INT,
    image_url VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0,
    skin_type VARCHAR(100),
    volume VARCHAR(50),
    usage_area VARCHAR(100),
    origin VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_up (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES cart(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    order_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    name          VARCHAR(100)   DEFAULT '',
    email         VARCHAR(100)   DEFAULT '',
    phone         VARCHAR(20)    DEFAULT '',
    order_date    DATETIME       DEFAULT CURRENT_TIMESTAMP,
    total_amount  DECIMAL(10,2)  NOT NULL,
    shipping_address TEXT        NOT NULL DEFAULT '',
    city          VARCHAR(100)   DEFAULT '',
    postal_code   VARCHAR(20)    DEFAULT '',
    shipping_cost DECIMAL(10,2)  DEFAULT 0,
    discount      DECIMAL(10,2)  DEFAULT 0,
    final_amount  DECIMAL(10,2)  DEFAULT 0,
    payment_method VARCHAR(60)   DEFAULT 'Kredi Kartı',
    order_status  VARCHAR(60)    DEFAULT 'Beklemede',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SAFE ALTER (add missing columns)
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS short_description VARCHAR(500) AFTER description,
    ADD COLUMN IF NOT EXISTS skin_type VARCHAR(100) AFTER rating,
    ADD COLUMN IF NOT EXISTS volume VARCHAR(50) AFTER skin_type;
ALTER TABLE brands
    ADD COLUMN IF NOT EXISTS country VARCHAR(100),
    ADD COLUMN IF NOT EXISTS founded_year INT;
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS name VARCHAR(100) DEFAULT '' AFTER last_name,
    ADD COLUMN IF NOT EXISTS role ENUM('user','admin') DEFAULT 'user';
ALTER TABLE reviews ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

-- ==================================
-- DATA
-- ==================================

-- Categories (4 skincare only)
DELETE FROM categories WHERE category_id IN (5,6);
INSERT IGNORE INTO categories (category_id, name, description) VALUES
(1,'Yüz Bakımı',   'Yüz temizleyiciler, nemlendiriciler, serumlar ve tonikler'),
(2,'Göz Bakımı',   'Göz çevresi kremleri, serumlar ve maskeler'),
(3,'Güneş Bakımı', 'Güneş koruyucu kremler ve güneş sonrası ürünler'),
(4,'Vücut Bakımı', 'Vücut losyonları, yağları ve duş ürünleri');

-- Brands (7)
INSERT IGNORE INTO brands (brand_id, name, description, country, founded_year) VALUES
(1,'Neutrogena',    'Dermatolojik test edilmiş cilt bakım ürünleri',         'ABD',      1930),
(2,'La Roche-Posay','Hassas ciltler için dermatoloji markası',               'Fransa',   1975),
(3,'Bioderma',      'Biyolojik uyumlu dermatolojik cilt bakımı',             'Fransa',   1977),
(4,'Cerave',        'Seramid içeren cilt bariyeri ürünleri',                 'ABD',      2005),
(5,'Avène',         'Termal su bazlı hassas cilt ürünleri',                  'Fransa',   1990),
(6,'Vichy',         'Mineralleştirilmiş volkanik su içeren dermatoloji',     'Fransa',   1931),
(7,'The Ordinary',  'Klinik formüllü, şeffaf içerikli seri',                 'İngiltere',2013);

-- Users (password = "password123")
-- Hash: $2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm
INSERT IGNORE INTO users (user_id,username,email,password,first_name,last_name,name,role) VALUES
(1,'admin','admin@avelin.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Admin','User','Admin User','admin'),
(2,'test_user','test@avelin.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Test','Kullanıcı','Test Kullanıcı','user'),
(3,'ayse_k','ayse@test.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Ayşe','K.','Ayşe K.','user'),
(4,'zeynep_d','zeynep@test.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Zeynep','D.','Zeynep D.','user'),
(5,'elif_y','elif@test.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Elif','Y.','Elif Y.','user'),
(6,'merve_t','merve@test.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Merve','T.','Merve T.','user'),
(7,'seda_a','seda@test.com','$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm','Seda','A.','Seda A.','user');

-- PRODUCTS (28 products, all 4 categories)
INSERT IGNORE INTO products (product_id,name,short_description,price,discount_price,stock_quantity,category_id,brand_id,image_url,rating,skin_type,volume) VALUES
-- Yüz Bakımı (10)
(1,'Hydro Boost Water Gel','Hyalüronik asit ile 48 saat nemlendirme.',129.99,99.99,45,1,1,'hydro_boost.jpg',4.7,'Normal/Yağlı Cilt','50 ml'),
(2,'Effaclar Duo+ Crème','Akne ve sivilce izlerine karşı dermatolog formülü.',189.99,159.99,30,1,2,'effaclar_duo.jpg',4.5,'Yağlı/Karma Cilt','40 ml'),
(3,'Q10 Anti-Age Night Cream','Q10 ile kırışıklık karşıtı gece bakımı.',79.99,64.99,55,1,1,'q10_night.jpg',4.3,'Normal/Kuru Cilt','50 ml'),
(4,'Sensibio H2O','Hassas ciltler için nazik misel su temizleyici.',109.99,89.99,60,1,3,'sensibio_h2o.jpg',4.8,'Hassas Cilt','250 ml'),
(5,'Eau Thermale Spray','Sakinleştirici termal su spreyi.',69.99,59.99,70,1,5,'thermal_spray.jpg',4.6,'Hassas/Tüm Cilt','150 ml'),
(6,'Moisturizing Cream','Seramid içerikli yoğun nemlendirici.',99.99,79.99,40,1,4,'moisturizing_cream.jpg',4.4,'Kuru Cilt','340 ml'),
(7,'Niacinamide 10% + Zinc 1%','Gözenek ve yağlanma kontrolü için serum.',89.99,74.99,50,1,7,'niacinamide_serum.jpg',4.6,'Yağlı/Karma Cilt','30 ml'),
(8,'Vitamin C 23% Suspension','C vitamini ile aydınlatıcı serum.',99.99,84.99,35,1,7,'vitamin_c_serum.jpg',4.2,'Tüm Cilt Tipleri','30 ml'),
(9,'Effaclar Gel Moussant','Yağlı ciltler için nazik temizleyici jel.',89.99,NULL,45,1,2,'salicylic_cleanser.jpg',4.4,'Yağlı/Karma Cilt','200 ml'),
(10,'Hyalu B5 Serum','Hyalüronik asit ile dolgunlaştırıcı serum.',249.99,199.99,20,1,2,'hyalu_b5.jpg',4.9,'Tüm Cilt Tipleri','30 ml'),
-- Göz Bakımı (5)
(11,'Redermic C Eyes Cream','Göz altı halkası ve şişkinlik karşıtı krem.',159.99,139.99,30,2,2,'eye_cream.jpg',4.5,'Tüm Cilt Tipleri','15 ml'),
(12,'Eye Repair Cream','Seramid içerikli göz onarım kremi.',129.99,109.99,25,2,4,'dark_circle_eye.jpg',4.3,'Tüm Cilt Tipleri','14 ml'),
(13,'Liftactiv Yeux Global','Sıkılaştırıcı göz serumu.',189.99,159.99,20,2,6,'lifting_eye.jpg',4.4,'Tüm Cilt Tipleri','15 ml'),
(14,'Rapid Wrinkle Eye Cream','Kırışıklık karşıtı göz kremi.',149.99,NULL,22,2,1,'eye_serum.jpg',4.2,'Tüm Cilt Tipleri','14 ml'),
(15,'Caffeine Solution 5% Eye','Kafein içerikli canlandırıcı göz serumu.',79.99,64.99,40,2,7,'eye_patch.jpg',4.1,'Tüm Cilt Tipleri','30 ml'),
-- Güneş Bakımı (5)
(16,'Fluid Solaire SPF 50+','Termal su içerikli hafif güneş koruyucu.',149.99,129.99,35,3,5,'spf50_sun.jpg',4.7,'Hassas/Tüm Cilt','50 ml'),
(17,'Anthelios UVmune 400 SPF50+','Ultrageniş spektrum UVA/UVB koruması.',199.99,169.99,28,3,2,'anthelios_spf50.jpg',4.8,'Tüm Cilt Tipleri','50 ml'),
(18,'Hydrating Mineral SPF30','Hassas ciltler için mineral güneş koruyucu.',139.99,NULL,32,3,4,'mineral_sunscreen.jpg',4.3,'Hassas Cilt','75 ml'),
(19,'Capital Soleil SPF 50','Yaşlanma karşıtı güneş serumu.',179.99,149.99,25,3,6,'sun_fluid_spf50.jpg',4.6,'Tüm Cilt Tipleri','40 ml'),
(20,'After-Sun Soothing Milk','Güneş sonrası yatıştırıcı krem.',99.99,84.99,38,3,3,'after_sun.jpg',4.5,'Güneş Gören Cilt','200 ml'),
-- Vücut Bakımı (8)
(21,'SA Smoothing Body Lotion','Seramid içerikli 24 saat nemlendiren losyon.',119.99,99.99,42,4,4,'body_lotion.jpg',4.5,'Kuru/Normal Cilt','473 ml'),
(22,'Sesame Formula Body Oil','Susam yağı içerikli vücut yağı.',89.99,NULL,36,4,1,'body_butter.jpg',4.3,'Kuru Cilt','200 ml'),
(23,'Atoderm Shower Oil','Atopik ciltler için nazik duş yağı.',99.99,84.99,30,4,3,'body_wash.jpg',4.4,'Hassas/Atopik Cilt','500 ml'),
(24,'Cellu Destock Serum','Selülit karşıtı sıkılaştırıcı vücut serumu.',159.99,139.99,20,4,6,'body_serum.jpg',4.1,'Tüm Cilt Tipleri','200 ml'),
(25,'Norwegian Formula Hand','Yoğun nemlendirici el kremi.',49.99,39.99,60,4,1,'hand_cream.jpg',4.6,'Kuru El','75 ml'),
(26,'Retinol 0.5% in Squalane','Saf retinol içeren gece serumu.',149.99,124.99,25,1,7,'retinol_serum.jpg',4.2,'Normal/Olgun Cilt','30 ml'),
(27,'Hydrabio Tonique','Alkolsüz dengeleyici tonik.',79.99,NULL,38,1,3,'toner_essentiel.jpg',4.3,'Tüm Cilt Tipleri','200 ml'),
(28,'Cleanance Micellar Water','Yağlı ciltler için matlaştırıcı misel su.',89.99,74.99,45,1,5,'micellar_water.jpg',4.4,'Yağlı/Karma Cilt','200 ml');

-- REVIEWS (2-3 per product)
DELETE FROM reviews;
INSERT INTO reviews (product_id,user_id,rating,comment) VALUES
(1,3,5,'Ürün çok başarılı, tekrar satın alacağım. Cildim gerçekten nemli görünüyor.'),
(1,4,4,'Hassas cildimde sorun oluşturmadı. Hafif formül çok iyi.'),
(2,5,5,'Sivilcelerim 2 haftada belirgin azaldı. Kesinlikle tavsiye ederim.'),
(2,6,4,'Ürün beklediğimden daha iyi çıktı. Tekrar satın alacağım.'),
(3,7,4,'Kırışıklıklarım için harika. Sabahları cildim çok daha canlı.'),
(3,3,5,'Gece kremi olarak mükemmel. Sabah cildimi pürüzsüz buluyor.'),
(4,4,5,'En sevdiğim temizleyici. Makyajı çıkarmak artık çok kolay.'),
(4,5,5,'Hassas cildimde sorun oluşturmadı, çok nazik formül.'),
(5,6,4,'Sprey çok pratik ve serinletici. Yaz için ideal.'),
(5,7,5,'Termal su gerçekten işe yarıyor. Cildim sakinledi.'),
(6,3,5,'Kuru cildim için birebir. Uzun süre nemli kalıyorum.'),
(6,4,4,'Seramid içeriği çok değerli. Cilt bariyerimi güçlendirdi.'),
(7,5,5,'Gözeneklerim küçüldü. Fiyat-performans açısından harika.'),
(7,6,4,'The Ordinary markasından ilk aldığım ürün, çok memnunum.'),
(8,7,4,'C vitamini cilt tonumu eşitledi. Aydınlık görünüyorum.'),
(9,3,4,'Temizleyici çok iyi. Yüzümü temizledikten sonra gergin hissetmiyorum.'),
(10,4,5,'En etkili serum bu. 3 haftada belirgin fark.'),
(10,5,5,'Hyalüronik asit serumu cildimi dolgunlaştırdı.'),
(11,6,4,'Göz altı şişliklerim azaldı. Tavsiye ederim.'),
(11,7,5,'Ürün çok başarılı, tekrar satın alacağım.'),
(12,3,4,'Hassas göz çevresinde sorun çıkarmadı.'),
(13,4,5,'Göz çevremde sıkılaşma hissettim.'),
(14,5,4,'Kafein içeriği göz şişkinliğimi azalttı.'),
(15,6,4,'Göz serumu çok etkili, düzenli kullanıyorum.'),
(16,7,5,'Bu güneş kremi çok hafif, makyaj altında mükemmel.'),
(16,3,4,'Hassas cildimde sorun oluşturmadı. Beyaz iz bırakmıyor.'),
(17,4,5,'Anthelios serisi en iyi güneş kremleri, yıllardır kullanıyorum.'),
(17,5,5,'Ürün beklediğimden daha iyi çıktı. Kesinlikle tavsiye.'),
(18,6,4,'Mineral içerikli olması büyük avantaj.'),
(19,7,5,'Hem güneş koruyor hem yaşlanma karşıtı etki. Harika!'),
(20,3,4,'Güneş sonrası cildimi çok rahatlatıyor.'),
(21,4,5,'Vücut için en iyi losyon. Koku yok, parfüm yok.'),
(21,5,4,'Seramid içeriği mükemmel. Kuruluğu tamamen gideren krem.'),
(22,6,4,'Susam yağı çok besleyici, cildi parlak yapıyor.'),
(23,7,5,'Hassas cildim için birebir duş jeli. Tahriş etmiyor.'),
(24,3,4,'Vücut serumu düzenli kullanınca cilt görünümü iyileşti.'),
(25,4,5,'En iyi el kremi, az miktarla büyük etki yapıyor.'),
(26,5,4,'Retinol serumuyla kırışıklıklarım azaldı.'),
(27,6,4,'Tonik cildimi dengeli tutuyor, diğer ürünleri emmeye hazırlıyor.'),
(28,7,5,'Yağlı cildim için vazgeçilmez. Gözenekleri derinlemesine temizliyor.');

-- Update ratings from reviews
UPDATE products p SET p.rating = (
    SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.product_id = p.product_id
) WHERE EXISTS (SELECT 1 FROM reviews r WHERE r.product_id = p.product_id);

COMMIT;
