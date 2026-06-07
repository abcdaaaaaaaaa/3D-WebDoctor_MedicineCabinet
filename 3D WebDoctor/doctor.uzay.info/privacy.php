<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gizlilik Politikası - 3D Web Doctor</title>
    <link class="shortcut icon" href="/images/doctor.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-indigo-100 selection:text-indigo-900">
    <div class="max-w-4xl mx-auto px-6 py-12">
        <div class="flex items-center justify-between mb-8">
            <a href="javascript:history.back()" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-indigo-600 transition-colors">
                <i class="fa-solid fa-arrow-left"></i> Geri Dön
            </a>
            <div class="flex items-center gap-2 text-indigo-600 font-bold text-xl">
                3D Web Doctor
            </div>
        </div>
        
        <main class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-slate-800 to-slate-900 px-8 py-12 text-white text-center">
                <h1 class="text-3xl font-bold mb-4">Gizlilik Politikası</h1>
                <p class="text-slate-300 max-w-2xl mx-auto">Kişisel verilerinizin, özellikle medikal verilerinizin güvenliği bizim için en yüksek ve kritik önceliğe sahiptir.</p>
            </div>
            
            <div class="p-8 md:p-12 space-y-8 text-slate-600 leading-relaxed">
                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-database text-indigo-500"></i> 1. Toplanan Veriler
                    </h2>
                    <p>Sistemin tıbbi değerlendirme fonksiyonlarını yerine getirebilmesi için kayıt ve kullanım süreçlerinde aşağıdaki kişisel ve medikal verileriniz toplanmaktadır:</p>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li><strong>Kimlik ve İletişim Bilgileri:</strong> Ad, Soyad, Doğum Tarihi ve T.C. Kimlik Numarası (T.C. Kimlik Numaralarınız veri tabanında hiçbir zaman açık metin olarak saklanmaz, kriptografik geri döndürülemez özetleme yöntemleri ile muhafaza edilir).</li>
                        <li><strong>Sağlık Verileri:</strong> Sisteme giriş yaptığınız kilo, ateş, semptomlar, hastalık geçmişi, alerjik reaksiyonlar ve kronik rahatsızlık beyanlarınız (Özel Rahatsızlık Bilgisi).</li>
                        <li><strong>Güvenlik ve Oturum Verileri:</strong> Parolalarınız, güvenlik sorularına (favori yemek/hayvan) verdiğiniz cevaplar ve güvenli doğrulama tokenları.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-microchip text-indigo-500"></i> 2. Verilerin Kullanım Amacı
                    </h2>
                    <p>Toplanan kişisel ve özel nitelikli sağlık verileriniz yalnızca sistemin medikal amaçları doğrultusunda şu işlevler için kullanılır:</p>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li>Hastanın fiziksel verilerine (yaş, kilo) ve semptom şiddetine göre miligram (mg) veya mililitre (mL) bazında güvenli medikal dozların algoritmik olarak hesaplanması.</li>
                        <li>Hastanın daha önceki ilaç kullanım kayıtları veya ret gerekçelerinin analiz edilerek ters etki yapabilecek ilaçların tespit edilmesi.</li>
                        <li>Platformun güvenlik altyapısının (bot koruması, CSRF token sistemi, çoklu sekme yönetimi) sağlanması.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-share-nodes text-indigo-500"></i> 3. Veri Paylaşımı ve Üçüncü Taraflar
                    </h2>
                    <p>Medikal bilgileriniz ve talepleriniz <strong>yalnızca</strong> onay kodunu girerek bağlantı kurduğunuz yetkili eczane ve o eczanenin kayıtlı eczacısı ile paylaşılır. Eczacı; medikal karar verebilmesi adına hastanın ilaç talebini, geçmiş ret kayıtlarını, boy/kilo endeksini ve varsa özel rahatsızlık (risk) beyanlarını görüntüleyebilir. Verileriniz bunun dışında hiçbir kişi, kurum, reklam ağı veya üçüncü taraf şirket ile paylaşılmaz ve satılmaz.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-lock text-indigo-500"></i> 4. Altyapı ve Veri Güvenliği
                    </h2>
                    <p>Kritik verilerinizin güvenliğini sağlamak için sistemimizde uçtan uca koruma uygulanmaktadır:</p>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li>Sisteme giriş yaptığınız T.C. Kimlik Numaraları ve oluşturduğunuz parolalar <code>password_hash()</code> fonksiyonu ve <code>PASSWORD_DEFAULT</code> algoritması kullanılarak şifrelenir ve veri tabanına işlenir. Sunucu yöneticileri dahi bu bilgilere açık metin formatında erişemez.</li>
                        <li>Dışarıdan gelebilecek form saldırılarına karşı her oturum için özel CSRF (Cross-Site Request Forgery) koruma token'ları, modern tarayıcı izolasyon politikaları (X-Frame-Options, X-XSS-Protection) kullanılmaktadır.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-file-contract text-indigo-500"></i> 5. KVKK ve Kullanıcı Hakları
                    </h2>
                    <p>6698 sayılı Kişisel Verilerin Korunması Kanunu (KVKK) uyarınca, sistemimizde yer alan kişisel verilerinizi inceleme, hatalı verilerin güncellenmesini (örneğin Özel Rahatsızlık Bilgisi alanını) talep etme hakkına sahipsiniz. Talep etmeniz halinde hesabınız kapatılabilir; ancak onaylanan/reddedilen ilaç geçmişleriniz eczane otomasyonunun hukuki güvenlik logları gereği kimliksizleştirilerek (anonim halde) belirli bir yasal süre boyunca arşivde tutulabilir.</p>
                </section>

                <section class="bg-slate-100 p-6 rounded-2xl border border-slate-200 mt-8">
                    <p class="mb-3 text-slate-700 font-medium">Bu şartlar zaman zaman güncellenebilir. Güncellenmiş şartlar bu sayfada yayınlandığı anda geçerli olur.</p>
                    <p class="text-slate-700">Kullanım koşulları için <a href="/terms" class="text-indigo-600 font-semibold hover:underline">Kullanım Şartları</a> sayfasını inceleyiniz.</p>
                </section>
            </div>
        </main>
    </div>
</body>
</html>