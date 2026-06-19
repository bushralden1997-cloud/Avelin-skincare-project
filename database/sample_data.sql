-- إضافة بيانات تجريبية باللغة التركية

-- إضافة مستخدمين
INSERT INTO users (username, email, password, first_name, last_name, address, phone, is_admin) VALUES
('admin', 'admin@skincare.com', '$2y$10$8KGQFEGHVPj0CtZXZ9JYZOiYYPQYDrUvTU4kNEWIhUzHzFYXPYzPa', 'Admin', 'User', 'İstanbul, Türkiye', '+90 555 123 4567', TRUE),
('mehmet', 'mehmet@example.com', '$2y$10$8KGQFEGHVPj0CtZXZ9JYZOiYYPQYDrUvTU4kNEWIhUzHzFYXPYzPa', 'Mehmet', 'Yılmaz', 'Ankara, Türkiye', '+90 555 234 5678', FALSE),
('ayse', 'ayse@example.com', '$2y$10$8KGQFEGHVPj0CtZXZ9JYZOiYYPQYDrUvTU4kNEWIhUzHzFYXPYzPa', 'Ayşe', 'Kaya', 'İzmir, Türkiye', '+90 555 345 6789', FALSE);

-- إضافة فئات المنتجات
INSERT INTO categories (name, description) VALUES
('Yüz Bakımı', 'Yüz temizleyiciler, nemlendiriciler, serumlar ve maskeler'),
('Göz Bakımı', 'Göz kremleri, göz maskeleri ve göz altı serumları'),
('Vücut Bakımı', 'Vücut losyonları, vücut yağları ve vücut peelinglari'),
('Güneş Bakımı', 'Güneş kremleri, güneş sonrası ürünler ve bronzlaştırıcılar'),
('Saç Bakımı', 'Şampuanlar, saç maskeleri ve saç yağları'),
('Makyaj', 'Fondötenler, rujlar, maskaralar ve allıklar');

-- إضافة العلامات التجارية
INSERT INTO brands (name, description, logo_url) VALUES
('Neutrogena', 'Dermatolojik cilt bakım ürünleri', 'neutrogena_logo.png'),
('La Roche-Posay', 'Hassas ciltler için dermatolojik ürünler', 'laroche_logo.png'),
('Nivea', 'Herkes için cilt bakım ürünleri', 'nivea_logo.png'),
('Bioderma', 'Dermatolojik cilt bakım ürünleri', 'bioderma_logo.png'),
('Avene', 'Hassas ciltler için termal su bazlı ürünler', 'avene_logo.png'),
('Cerave', 'Seramid içeren cilt bakım ürünleri', 'cerave_logo.png');

-- إضافة المنتجات
INSERT INTO products (name, description, price, discount_price, stock_quantity, category_id, brand_id, image_url, rating) VALUES
('Hydro Boost Nemlendirici', 'Hyalüronik asit içeren su bazlı nemlendirici', 129.99, 99.99, 50, 1, 1, 'hydro_boost.jpg', 4.7),
('Effaclar Duo+ Akne Kremi', 'Akne ve sivilce izleri için bakım kremi', 189.99, 159.99, 30, 1, 2, 'effaclar_duo.jpg', 4.5),
('Q10 Anti-Aging Gece Kremi', 'Kırışıklık karşıtı gece bakım kremi', 79.99, 69.99, 40, 1, 3, 'q10_cream.jpg', 4.3),
('Sensibio H2O Misel Su', 'Hassas ciltler için temizleyici misel su', 109.99, 89.99, 60, 1, 4, 'sensibio.jpg', 4.8),
('Thermal Spring Water Spray', 'Hassas ciltler için termal su spreyi', 69.99, 59.99, 70, 1, 5, 'thermal_water.jpg', 4.6),
('Moisturizing Cream', 'Kuru ciltler için nemlendirici krem', 99.99, 79.99, 45, 1, 6, 'cerave_cream.jpg', 4.4),
('Retinol Serum', 'Kırışıklık karşıtı retinol serumu', 199.99, 169.99, 25, 1, 1, 'retinol_serum.jpg', 4.2),
('Hyalu B5 Serum', 'Hyalüronik asit ve B5 vitamini içeren serum', 249.99, 199.99, 20, 1, 2, 'hyalu_b5.jpg', 4.9),
('Güneş Kremi SPF 50+', 'Yüksek koruma faktörlü güneş kremi', 149.99, 129.99, 35, 4, 5, 'sunscreen.jpg', 4.7),
('Göz Çevresi Kremi', 'Göz altı morlukları ve şişlikler için krem', 159.99, 139.99, 30, 2, 2, 'eye_cream.jpg', 4.5);

-- إضافة صور إضافية للمنتجات
INSERT INTO product_images (product_id, image_url, is_primary) VALUES
(1, 'hydro_boost_1.jpg', TRUE),
(1, 'hydro_boost_2.jpg', FALSE),
(1, 'hydro_boost_3.jpg', FALSE),
(2, 'effaclar_duo_1.jpg', TRUE),
(2, 'effaclar_duo_2.jpg', FALSE),
(3, 'q10_cream_1.jpg', TRUE),
(3, 'q10_cream_2.jpg', FALSE),
(4, 'sensibio_1.jpg', TRUE),
(4, 'sensibio_2.jpg', FALSE),
(5, 'thermal_water_1.jpg', TRUE);

-- إضافة تقييمات للمنتجات
INSERT INTO reviews (product_id, user_id, rating, comment) VALUES
(1, 2, 5, 'Harika bir ürün, cildim çok daha nemli ve parlak görünüyor.'),
(1, 3, 4, 'Çok beğendim, cildimdeki kuruluğu giderdi.'),
(2, 2, 4, 'Sivilcelerim azaldı, memnunum.'),
(3, 3, 5, 'Kırışıklıklarım için harika çalışıyor.'),
(4, 2, 5, 'En sevdiğim misel su, makyajımı çok iyi temizliyor.'),
(5, 3, 4, 'Çok ferahlatıcı, özellikle yaz aylarında.'),
(6, 2, 4, 'Kuru cildim için mükemmel bir nemlendirici.'),
(7, 3, 3, 'İyi bir ürün ama biraz tahriş edici olabiliyor.'),
(8, 2, 5, 'Cildim çok daha dolgun ve nemli görünüyor.'),
(9, 3, 5, 'Güneşten çok iyi koruyor ve beyaz iz bırakmıyor.'),
(10, 2, 4, 'Göz altı torbalarım için iyi geldi.');
