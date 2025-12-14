CREATE TABLE MUSTERI (
    MusteriID INT PRIMARY KEY IDENTITY(1,1),
    Ad NVARCHAR(50) NOT NULL,
    Soyad NVARCHAR(50) NOT NULL,
    Eposta NVARCHAR(100) NOT NULL UNIQUE,
    SifreHash NVARCHAR(255) NOT NULL,
    KayitTarihi DATE DEFAULT GETDATE()
);

CREATE TABLE KATEGORI (
    KategoriID INT PRIMARY KEY IDENTITY(1,1),
    KategoriAdi NVARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE MARKA (
    MarkaID INT PRIMARY KEY IDENTITY(1,1),
    MarkaAdi NVARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE URUN (
    UrunID INT PRIMARY KEY IDENTITY(1,1),
    UrunAdi NVARCHAR(255) NOT NULL,
    Aciklama NVARCHAR(MAX),
    Fiyat DECIMAL(10, 2) NOT NULL CHECK (Fiyat >= 0),
    StokMiktari INT NOT NULL CHECK (StokMiktari >= 0),
    KategoriID INT NOT NULL,
    MarkaID INT,
    FOREIGN KEY (KategoriID) REFERENCES KATEGORI(KategoriID),
    FOREIGN KEY (MarkaID) REFERENCES MARKA(MarkaID)
);

CREATE TABLE ADRES (
    AdresID INT PRIMARY KEY IDENTITY(1,1),
    MusteriID INT NOT NULL,
    AdresBaslik NVARCHAR(50) NOT NULL,
    AdresMetni NVARCHAR(255) NOT NULL,
    Sehir NVARCHAR(50) NOT NULL,
    AdresTipi NVARCHAR(10) CHECK (AdresTipi IN ('Teslimat', 'Fatura')),
    FOREIGN KEY (MusteriID) REFERENCES MUSTERI(MusteriID)
);


CREATE TABLE SIPARIS (
    SiparisID INT PRIMARY KEY IDENTITY(1,1),
    MusteriID INT NOT NULL,
    SiparisTarihi DATETIME DEFAULT GETDATE(),
    ToplamTutar DECIMAL(10, 2) NOT NULL, -- Ba�lang��ta 0.00 olabilir, sonra UPDATE edilir.
    TeslimatAdresID INT NOT NULL,
    FOREIGN KEY (MusteriID) REFERENCES MUSTERI(MusteriID),
    FOREIGN KEY (TeslimatAdresID) REFERENCES ADRES(AdresID)
);

CREATE TABLE SIPARIS_KALEMI (
    SiparisID INT NOT NULL,
    UrunID INT NOT NULL,
    Miktar INT NOT NULL CHECK (Miktar > 0),
    BirimFiyat DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (SiparisID, UrunID),
    FOREIGN KEY (SiparisID) REFERENCES SIPARIS(SiparisID),
    FOREIGN KEY (UrunID) REFERENCES URUN(UrunID)
);


CREATE TABLE ODEME (
    OdemeID INT PRIMARY KEY IDENTITY(1,1),
    SiparisID INT NOT NULL UNIQUE, -- Bire-bir ili�ki i�in UNIQUE k�s�tlamas�
    OdemeTipi NVARCHAR(50),
    Tutar DECIMAL(10, 2) NOT NULL,
    IslemDurumu NVARCHAR(20) CHECK (IslemDurumu IN ('Basarili', 'Beklemede', 'Reddedildi')),
    OdemeTarihi DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (SiparisID) REFERENCES SIPARIS(SiparisID)
);

CREATE TABLE KARGO_TESLIMAT (
    TeslimatID INT PRIMARY KEY IDENTITY(1,1),
    SiparisID INT NOT NULL UNIQUE, -- Bire-bir ili�ki i�in UNIQUE k�s�tlamas�
    KargoSirketi NVARCHAR(100),
    TakipNumarasi NVARCHAR(50) UNIQUE,
    TahminiTeslimTarihi DATE,
    FOREIGN KEY (SiparisID) REFERENCES SIPARIS(SiparisID)
);

CREATE TABLE SIPARIS_DURUM_LOGU (
    LogID INT PRIMARY KEY IDENTITY(1,1),
    SiparisID INT NOT NULL,
    DurumAciklamasi NVARCHAR(50) NOT NULL,
    DurumTarihi DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (SiparisID) REFERENCES SIPARIS(SiparisID)
);

CREATE TABLE URUN_YORUM (
    YorumID INT PRIMARY KEY IDENTITY(1,1),
    MusteriID INT NOT NULL,
    UrunID INT NOT NULL,
    Puan INT NOT NULL CHECK (Puan BETWEEN 1 AND 5),
    YorumMetni NVARCHAR(MAX),
    YorumTarihi DATETIME DEFAULT GETDATE(),
    FOREIGN KEY (MusteriID) REFERENCES MUSTERI(MusteriID),
    FOREIGN KEY (UrunID) REFERENCES URUN(UrunID)
);

-- 1. MUSTERI Tablosuna Veri Ekleme
INSERT INTO MUSTERI (Ad, Soyad, Eposta, SifreHash) VALUES
('Admin', 'Hepsişurada', 'admin@hepsisurada.com', '$2y$10$6G/dPUPMsxwY2OHl.BO1JOmhDmxra5hacpbnbQLRl/tO..gVi7fLO'), -- MusteriID 1
('Mustafa Aziz', 'Akıllı', 'aziz@gmail.com', '$2y$10$i7E9wRd0wcalUunQXrgViOsW3cdQQHajCDMkESOGDEHR4jKQNBu1G'), -- MusteriID 1
('İbrahim Tarık', 'Solak', 'solak@gmail.com', '$2y$10$i7E9wRd0wcalUunQXrgViOsW3cdQQHajCDMkESOGDEHR4jKQNBu1G'), -- MusteriID 2
('Muhammed Hüseyin', 'Özkaya', 'huseyin@gmail.com', '$2y$10$i7E9wRd0wcalUunQXrgViOsW3cdQQHajCDMkESOGDEHR4jKQNBu1G'); -- MusteriID 3

-- 2. KATEGORI Tablosuna Veri Ekleme
INSERT INTO KATEGORI (KategoriAdi) VALUES
('Elektronik'), -- KategoriID 1
('Giyim'),      -- KategoriID 2
('Ev ve Yaşam'); -- KategoriID 3

-- 3. MARKA Tablosuna Veri Ekleme
INSERT INTO MARKA (MarkaAdi) VALUES
('Apple'),   -- MarkaID 1
('Samsung'), -- MarkaID 2
('Nike');    -- MarkaID 3

-- 4. URUN Tablosuna Veri Ekleme
INSERT INTO URUN (UrunAdi, Aciklama, Fiyat, StokMiktari, KategoriID, MarkaID) VALUES
('iPhone 15 Pro', 'Yeni nesil akıllı telefon.', 45999.50, 50, 1, 1), -- UrunID 1
('Galaxy S23', 'Samsung amiral gemisi telefon.', 35000.00, 30, 1, 2), -- UrunID 2
('Spor Tişört', 'Nefes alan kumaştan spor tişört.', 899.90, 200, 2, 3), -- UrunID 3
('Akıllı Süpürge', 'Robot süpürge, haritalama özellikli.', 12500.00, 15, 3, 2); -- UrunID 4

-- 5. ADRES Tablosuna Veri Ekleme
INSERT INTO ADRES (MusteriID, AdresBaslik, AdresMetni, Sehir, AdresTipi) VALUES
(1, 'Ev Adresi', 'Fatih Mah. Karanfil Sok. No: 5/A', 'İstanbul', 'Teslimat'),
(1, 'İş Adresi', 'Anadolu Cad. Plaza Ofis No: 12', 'İstanbul', 'Fatura'),
(2, 'Ana Adres', 'Kızılay Cad. Lale Sokak No: 8', 'Ankara', 'Teslimat'),    
(3, 'Fatura Adresi', 'İzmir Caddesi No: 45/B', 'İzmir', 'Fatura');     
-- 6. SIPARIS Tablosuna Veri Ekleme
-- Toplam Tutar başlangıçta 0.00 veya hesaplanmış bir değer olabilir.
INSERT INTO SIPARIS (MusteriID, TeslimatAdresID, ToplamTutar) VALUES
(1, 1, 47809.30),
(2, 3, 35000.00),
(3, 4, 12500.00);

-- 7. SIPARIS_KALEMI Tablosuna Veri Ekleme
-- Sipariş 1 (Ali Yılmaz): iPhone 15 Pro (1 adet) ve Spor Tişört (2 adet)
INSERT INTO SIPARIS_KALEMI (SiparisID, UrunID, Miktar, BirimFiyat) VALUES
(1, 1, 1, 45999.50), -- iPhone 15 Pro
(1, 3, 2, 899.90);   -- Spor Tişört

-- Sipariş 2 (Ayşe Demir): Galaxy S23 (1 adet)
INSERT INTO SIPARIS_KALEMI (SiparisID, UrunID, Miktar, BirimFiyat) VALUES
(2, 2, 1, 35000.00); -- Galaxy S23

-- Sipariş 3 (Mehmet Kaya): Akıllı Süpürge (1 adet)
INSERT INTO SIPARIS_KALEMI (SiparisID, UrunID, Miktar, BirimFiyat) VALUES
(3, 4, 1, 12500.00); -- Akıllı Süpürge

-- 8. ODEME Tablosuna Veri Ekleme
INSERT INTO ODEME (SiparisID, OdemeTipi, Tutar, IslemDurumu) VALUES
(1, 'Kredi Kartı', 47809.30, 'Başarılı'), -- Siparis 1: Başarılı ödeme
(2, 'Havale/EFT', 35000.00, 'Başarılı'), -- Siparis 2: Başarılı ödeme
(3, 'Kapıda Ödeme', 12500.00, 'Beklemede'); -- Siparis 3: Ödeme beklemede

-- 9. KARGO_TESLIMAT Tablosuna Veri Ekleme
INSERT INTO KARGO_TESLIMAT (SiparisID, KargoSirketi, TakipNumarasi, TahminiTeslimTarihi) VALUES
(1, 'Hızlı Kargo', 'HK123456789', '2025-11-05'),
(2, 'Mega Kargo', 'MK987654321', '2025-11-07');
-- Siparis 3 henüz kargolanmamış varsayılmıştır.

-- 10. SIPARIS_DURUM_LOGU Tablosuna Veri Ekleme
INSERT INTO SIPARIS_DURUM_LOGU (SiparisID, DurumAciklamasi, DurumTarihi) VALUES
-- Sipariş 1 Geçmişi
(1, 'Sipariş Alındı', DATEADD(MINUTE, -10, GETDATE())),
(1, 'Ödeme Onaylandı', DATEADD(MINUTE, -5, GETDATE())),
(1, 'Kargoya Verildi', GETDATE()),
-- Sipariş 2 Geçmişi
(2, 'Sipariş Alındı', DATEADD(MINUTE, -20, GETDATE())),
(2, 'Ödeme Onaylandı', DATEADD(MINUTE, -15, GETDATE())),
(2, 'Teslim Edildi', DATEADD(DAY, -1, GETDATE())),
-- Sipariş 3 Geçmişi
(3, 'Sipariş Alındı', DATEADD(MINUTE, -5, GETDATE()));

-- 11. URUN_YORUM Tablosuna Veri Ekleme

INSERT INTO URUN_YORUM (MusteriID, UrunID, Puan, YorumMetni) VALUES
(1, 1, 5, 'Ürün hızlı geldi ve beklentimin üstünde. Harika telefon!'),
(2, 2, 4, 'Galaxy S23 beklentilerimi karşıladı. Tavsiye ederim.'),
(1, 3, 3, 'Tişört fena değil, kumaşı güzel ama kargo yavaştı.'); -- Müşteri 1, Ürün 3'ü de satın aldı.
