# OIM (Öğrenci İşleri Merkezi)

Kurum içi gereksinim nedeniyle oluşturulmuş basit bir öğrenci işleri merkezi betiği.

/!\ Henüz geliştirilme aşamasındadır ve hataları ile fonksiyonel çalışmayan alanları olabilir /!\

### Geliştirildiği ortam & gereksinimler
* PHP 8.2
* MySQL 5.7.39 (PDO)
* Composer

### Composer ile kurulması gerekenler
```
"infobip/infobip-api-php-client": "^5.1"
"ext-pdo": "*"
"phpmailer/phpmailer": "^6.5"
"phpoffice/phpspreadsheet": "^1.29"
"dompdf/dompdf": "^2.0"
"league/iso3166": "^4.3"
"giggsey/libphonenumber-for-php": "^8.13"
"ext-gd": "^8.2"
```

### Neler kullanıldı
* [Bootstrap 5](https://github.com/twbs/bootstrap)
* [Pikaday](https://github.com/Pikaday/Pikaday)
* [Datatables](https://github.com/DataTables/DataTables)
* [Select2](https://github.com/select2/select2)

### Ek bilgiler
* Veritabanı için oim.sql dosyası kullanılabilir

### Özellikler
* Ders planlayabilme (Öğretmen - Öğrenci - Ders - Akademi - Sınıf)
* Tanışma dersi planlayabilme (Öğretmen - Öğrenci - Ders - Akademi - Sınıf)
* Telafi dersi planlayabilme (Öğretmen - Öğrenci - Ders - Akademi - Sınıf)
* Öğrencilerin kendi panellerinden ders planları ve ödemelerinin takibi
* Öğretmenlerin kendi panellerinden ders planları ve tahmini alacaklarının takibi
* Kullanıcılara özel oturum aç, şifremi unuttum özelliği
* Kullanıcıların kendi bilgilerini güncelleyebildikleri kontrol paneli
* Kullanıcı ile birimlerin ilişkilendirilmesi
* Yöneticilere özel oturum aç, şifremi unuttum özelliği
* Yönetici oturum açtığında mobil telefon numarasına SMS gönderilmesi
* Yöneticinin kendi bilgilerini güncelleyebilmesi
* Gelişmiş yönetici paneli
* Yönetim panelinden öğrenci ekleyip, düzenleyip, silebilme
* Yönetim panelinden kullanıcı ekleyip, düzenleyip, silebilme
* Yönetim panelinden sınıf ekleyip, düzenleyip, silebilme
* Yönetim panelinden yönetici ekleyip, düzenleyip, silebilme
* Öğrenci, Öğretmen, Kullanıcı, Ders, Sınıf arayabilme
* Akademiz bazında yetki sistemi (Yönetici, Koordinatör, Eğitim Danışmanı, Öğretmen, Veli, Öğrenci)
* Karanlık / Aydınlık mod desteği
* Infobip ile SMS gönderimi
* SMTP ile e-posta gönderimi 
* Özel SMS bağlantısı ile kullanıcı doğrulaması (IP adresi ve zaman damgası kaydı)
* Özel e-posta bağlantısı ile kullanıcı doğrulaması (IP adresi ve zaman damgası kaydı)
* Kullanıcı doğrulamasının yinelenebilmesi
* Kullanıcı doğrulama geçmişini görüntüleyebilme
* Öğrenci ve öğretmenler için doğum günü takibi
* Öğrenci ve Öğretmenlere özel günlerde kutlama SMS ve e-postası gönderilmesi (?)
* Öğrenci ve Öğretmenlere katılım sağlayacakları eğitim hakkında SMS ve e-postası gönderilmesi (?)
* Çoklu lokasyon ve birime özel öğrenci, öğretmen, ders, muhasebe kayıtları
* Basit muhasebe kayıtlarının tutulması (Geliştiriliyor)
* Muhasebe kayıtlarını Excel formatında rapor alabilme
* Akademi bazında günlük PDF ciro raporu alabilme
* Akademi - Gün bazında ders planı raporu alabilme
* Ders bazında PDF fatura istemi alabilme
* Öğrenci bireysel ve kurumsal fatura bilgilerinin tuutlması 
* Kullanıcı oluşturulduğunda SMS ve e-posta ile kullanıcı adı, şifre bilgisi iletilmesi
* reCaptcha v3 desteği
* Kullanıcıdan SMS ve e-posta ile dijital imza alınması
* Öğrenci ile veli ilişkilendirilmesi
* Kullanıcı profillerine fotoğraf yükleyebilme
* Kullanıcı özelinde özel SMS ve e-posta gönderebilme
* Kullanıcı özelinde gönderilmiş özel SMS ve e-posta geçmişini görüntüleyebilme
* Kullanıcı aktif/pasif hale getirebilme
* Kullanıcının kendi hesabını dondurabilmesi
* Duyuru sistemi
* Çoklu dil desteği (TR, EN ve artırılabilir)
* Kullanıcı kendi panelinden dosyalar yükleyebilir ve görüntüleyebilir
* Profil fotoğrafı özelliği eklendi
* Site seçenekleri yönetici panelinden değiştirilebilir
* ...

? = Geliştiriliyor

### Güvenlik
Bir güvenlik açığı bulduğunuzu düşünüyorsanız (veya emin değilseniz) benimle iletişime geçmek e-posta adresimi kullanabilirsiniz.




