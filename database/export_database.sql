-- CiltBakım E-ticaret Veritabanı
-- XAMPP Version 5.5.5-10.1.10 için uyumlu

-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS ciltbakim DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE ciltbakim;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `registration_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Kategoriler tablosu
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `parent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`category_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Markalar tablosu
CREATE TABLE IF NOT EXISTS `brands` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `logo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Ürünler tablosu
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `brand_id` int(11) NOT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('active','inactive','out_of_stock') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Ürün özellikleri tablosu
CREATE TABLE IF NOT EXISTS `product_attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  PRIMARY KEY (`attribute_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Favoriler tablosu
CREATE TABLE IF NOT EXISTS `favorites` (
  `favorite_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `added_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Sepet tablosu
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Sepet ürünleri tablosu
CREATE TABLE IF NOT EXISTS `cart_items` (
  `cart_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `added_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  KEY `cart_id` (`cart_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Siparişler tablosu
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `final_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Sipariş ürünleri tablosu
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Örnek veriler ekleme

-- Kategoriler
INSERT INTO `categories` (`category_id`, `name`, `description`, `parent_id`, `status`) VALUES
(1, 'Yüz Bakımı', 'Yüz bakım ürünleri', NULL, 'active'),
(2, 'Vücut Bakımı', 'Vücut bakım ürünleri', NULL, 'active'),
(3, 'Saç Bakımı', 'Saç bakım ürünleri', NULL, 'active'),
(4, 'Makyaj', 'Makyaj ürünleri', NULL, 'active'),
(5, 'Temizleyiciler', 'Yüz temizleme ürünleri', 1, 'active'),
(6, 'Nemlendiriciler', 'Yüz nemlendirici ürünleri', 1, 'active'),
(7, 'Serumlar', 'Yüz bakım serumları', 1, 'active'),
(8, 'Güneş Koruyucular', 'Güneş koruyucu ürünler', 1, 'active'),
(9, 'Vücut Kremleri', 'Vücut nemlendirici kremler', 2, 'active'),
(10, 'Vücut Yağları', 'Vücut bakım yağları', 2, 'active'),
(11, 'El Bakımı', 'El bakım ürünleri', 2, 'active'),
(12, 'Ayak Bakımı', 'Ayak bakım ürünleri', 2, 'active');

-- Markalar
INSERT INTO `brands` (`brand_id`, `name`, `description`, `logo`, `status`) VALUES
(1, 'Neutrogena', 'Dermatolojik cilt bakım ürünleri', 'neutrogena.png', 'active'),
(2, 'La Roche-Posay', 'Hassas ciltler için dermatolojik ürünler', 'laroche.png', 'active'),
(3, 'Bioderma', 'Dermatolojik cilt bakım ürünleri', 'bioderma.png', 'active'),
(4, 'Avene', 'Hassas ciltler için termal su bazlı ürünler', 'avene.png', 'active'),
(5, 'Cerave', 'Seramid içerikli cilt bakım ürünleri', 'cerave.png', 'active'),
(6, 'The Ordinary', 'Aktif içerikli cilt bakım ürünleri', 'ordinary.png', 'active'),
(7, 'Garnier', 'Uygun fiyatlı cilt bakım ürünleri', 'garnier.png', 'active'),
(8, 'Nivea', 'Klasik cilt bakım ürünleri', 'nivea.png', 'active'),
(9, 'Loreal Paris', 'Geniş ürün yelpazesi sunan kozmetik markası', 'loreal.png', 'active'),
(10, 'Vichy', 'Termal su bazlı dermokozmetik ürünler', 'vichy.png', 'active');

-- Ürünler
INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `discount_price`, `stock_quantity`, `image`, `category_id`, `brand_id`, `featured`, `status`, `created_at`) VALUES
(1, 'Hydro Boost Nemlendirici Jel', 'Hyaluronik asit içeren su bazlı nemlendirici jel. Yağsız formülü ile tüm cilt tiplerine uygundur.', 129.90, 99.90, 50, 'hydroboost.jpg', 6, 1, 1, 'active', '2025-04-19 10:00:00'),
(2, 'Effaclar Duo+ Akne Kremi', 'Akne ve sivilce izlerine karşı etkili bakım kremi. Gözenekleri sıkılaştırır ve cilt dokusunu düzenler.', 189.90, NULL, 35, 'effaclar.jpg', 6, 2, 1, 'active', '2025-04-19 10:05:00'),
(3, 'Sensibio H2O Misel Su', 'Hassas ciltler için temizleyici ve makyaj çıkarıcı misel su. Durulama gerektirmez.', 149.90, 129.90, 60, 'sensibio.jpg', 5, 3, 1, 'active', '2025-04-19 10:10:00'),
(4, 'Thermal Spring Water Spray', 'Hassas ciltler için sakinleştirici termal su spreyi. Tazeleyici ve yatıştırıcı etki sağlar.', 99.90, NULL, 45, 'thermalwater.jpg', 6, 4, 0, 'active', '2025-04-19 10:15:00'),
(5, 'Moisturizing Cream', 'Seramid içeren nemlendirici krem. Cildin nem bariyerini güçlendirir ve korur.', 159.90, 139.90, 40, 'ceravecream.jpg', 6, 5, 1, 'active', '2025-04-19 10:20:00'),
(6, 'Niacinamide 10% + Zinc 1%', 'Gözenek görünümünü azaltan ve cilt tonunu düzenleyen serum. Yağlı ciltler için idealdir.', 119.90, NULL, 30, 'niacinamide.jpg', 7, 6, 1, 'active', '2025-04-19 10:25:00'),
(7, 'Vitamin C Serum', 'C vitamini içeren aydınlatıcı ve ton eşitleyici serum. Antioksidan koruma sağlar.', 149.90, 129.90, 25, 'vitaminc.jpg', 7, 9, 0, 'active', '2025-04-19 10:30:00'),
(8, 'Hyaluronic Acid 2% + B5', 'Cildi derinlemesine nemlendiren ve dolgunlaştıran hyaluronik asit serumu.', 109.90, NULL, 35, 'hyaluronic.jpg', 7, 6, 0, 'active', '2025-04-19 10:35:00'),
(9, 'Anthelios XL SPF 50+', 'Yüksek koruma faktörlü güneş kremi. Suya dayanıklı ve yağsız formül.', 199.90, 169.90, 40, 'anthelios.jpg', 8, 2, 1, 'active', '2025-04-19 10:40:00'),
(10, 'Cica Cream', 'Hassas ve tahriş olmuş ciltler için onarıcı bakım kremi. Centella Asiatica içerir.', 169.90, NULL, 30, 'cicacream.jpg', 6, 10, 0, 'active', '2025-04-19 10:45:00'),
(11, 'Micellar Cleansing Water', 'Tüm cilt tipleri için makyaj temizleyici ve arındırıcı misel su.', 89.90, 69.90, 55, 'micellar.jpg', 5, 7, 0, 'active', '2025-04-19 10:50:00'),
(12, 'Soft Moisturizing Cream', 'Tüm cilt tipleri için günlük nemlendirici krem. Hafif ve yağsız formül.', 79.90, NULL, 60, 'niveacream.jpg', 6, 8, 0, 'active', '2025-04-19 10:55:00'),
(13, 'Retinol 0.5% in Squalane', 'Kırışıklık karşıtı ve cilt dokusunu düzenleyici retinol serumu.', 129.90, NULL, 25, 'retinol.jpg', 7, 6, 1, 'active', '2025-04-19 11:00:00'),
(14, 'Toleriane Ultra Cream', 'Ultra hassas ve alerjiye eğilimli ciltler için yoğun nemlendirici bakım.', 179.90, 159.90, 30, 'toleriane.jpg', 6, 2, 0, 'active', '2025-04-19 11:05:00'),
(15, 'Hydrating Cleanser', 'Nemlendirici içerikli yüz temizleme jeli. Cildi kurutmadan temizler.', 119.90, NULL, 45, 'hydrating.jpg', 5, 5, 0, 'active', '2025-04-19 11:10:00'),
(16, 'Mineral 89 Hyaluronic Acid Gel', 'Cildi güçlendiren ve dolgunlaştıran hyaluronik asit içerikli jel.', 189.90, 169.90, 35, 'mineral89.jpg', 7, 10, 1, 'active', '2025-04-19 11:15:00'),
(17, 'Atoderm Intensive Baume', 'Çok kuru ve atopik ciltler için yoğun nemlendirici vücut balmı.', 159.90, NULL, 40, 'atoderm.jpg', 9, 3, 0, 'active', '2025-04-19 11:20:00'),
(18, 'Lipikar Baume AP+', 'Çok kuru ciltler için lipid bakımı sağlayan vücut balmı.', 169.90, 149.90, 35, 'lipikar.jpg', 9, 2, 1, 'active', '2025-04-19 11:25:00'),
(19, 'Glycolic Acid 7% Toning Solution', 'Cilt dokusunu düzenleyen ve aydınlatan glikolik asit tonik.', 139.90, NULL, 30, 'glycolic.jpg', 5, 6, 0, 'active', '2025-04-19 11:30:00'),
(20, 'Revitalift Laser X3 Cream', 'Kırışıklık karşıtı ve sıkılaştırıcı yüz bakım kremi.', 199.90, 179.90, 25, 'revitalift.jpg', 6, 9, 1, 'active', '2025-04-19 11:35:00');

-- Örnek kullanıcı
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `address`, `registration_date`, `status`) VALUES
(1, 'Test Kullanıcı', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '5551234567', 'Test Adres, İstanbul', '2025-04-19 12:00:00', 'active');

-- Örnek ürün özellikleri
INSERT INTO `product_attributes` (`product_id`, `attribute_name`, `attribute_value`) VALUES
(1, 'Cilt Tipi', 'Tüm Cilt Tipleri'),
(1, 'İçerik', 'Hyaluronik Asit'),
(1, 'Miktar', '50ml'),
(2, 'Cilt Tipi', 'Yağlı ve Akneye Eğilimli Cilt'),
(2, 'İçerik', 'Niacinamide, LHA'),
(2, 'Miktar', '40ml'),
(3, 'Cilt Tipi', 'Hassas Cilt'),
(3, 'İçerik', 'Misel Teknolojisi'),
(3, 'Miktar', '500ml'),
(5, 'Cilt Tipi', 'Kuru ve Çok Kuru Cilt'),
(5, 'İçerik', 'Seramid, Hyaluronik Asit'),
(5, 'Miktar', '340g'),
(6, 'Cilt Tipi', 'Yağlı ve Karma Cilt'),
(6, 'İçerik', 'Niacinamide, Çinko'),
(6, 'Miktar', '30ml'),
(9, 'Cilt Tipi', 'Tüm Cilt Tipleri'),
(9, 'İçerik', 'Mexoryl XL, Titanium Dioxide'),
(9, 'Miktar', '50ml'),
(9, 'SPF', '50+');

-- Örnek favoriler
INSERT INTO `favorites` (`user_id`, `product_id`) VALUES
(1, 1),
(1, 5),
(1, 9),
(1, 13);

-- Örnek sepet
INSERT INTO `cart` (`cart_id`, `user_id`) VALUES
(1, 1);

-- Örnek sepet ürünleri
INSERT INTO `cart_items` (`cart_id`, `product_id`, `quantity`) VALUES
(1, 2, 1),
(1, 6, 2),
(1, 9, 1);

-- Örnek sipariş
INSERT INTO `orders` (`order_id`, `user_id`, `name`, `email`, `phone`, `address`, `city`, `postal_code`, `payment_method`, `total_amount`, `discount`, `shipping_cost`, `final_amount`, `status`, `order_date`) VALUES
(1, 1, 'Test Kullanıcı', 'test@example.com', '5551234567', 'Test Adres', 'İstanbul', '34000', 'credit_card', 429.70, 42.97, 0.00, 386.73, 'delivered', '2025-04-18 15:30:00');

-- Örnek sipariş ürünleri
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 99.90),
(1, 3, 1, 129.90),
(1, 5, 1, 139.90),
(1, 11, 1, 59.90);
