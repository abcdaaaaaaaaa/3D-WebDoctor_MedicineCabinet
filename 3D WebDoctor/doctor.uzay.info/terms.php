<?php
session_start();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanım Şartları - 3D Web Doctor</title>
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
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-8 py-12 text-white text-center">
                <h1 class="text-3xl font-bold mb-4">Kullanım Şartları</h1>
                <p class="text-indigo-100 max-w-2xl mx-auto">Sistemimizi kullanmadan önce lütfen aşağıdaki şartları dikkatlice okuyunuz.</p>
            </div>
            
            <div class="p-8 md:p-12 space-y-8 text-slate-600 leading-relaxed">
                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-indigo-500"></i> 1. Kabul ve Onay
                    </h2>
                    <p>Bu web sitesini veya medikal asistan sistemini ("3D Web Doctor") kullanarak, bu Kullanım Şartları'nı ve Gizlilik Politikası'nı tamamen kabul etmiş sayılırsınız. Eğer bu şartlardan herhangi birini kabul etmiyorsanız, lütfen sistemi kullanmayınız veya kayıt işleminizi iptal ediniz.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-stethoscope text-indigo-500"></i> 2. Tıbbi Sorumluluk Reddi
                    </h2>
                    <p>3D Web Doctor sistemi, kullanıcılara yaş, kilo ve belirttikleri semptomlara dayalı olarak tahmini ilaç dozaj hesaplaması ve eczane eşleştirmesi sağlayan bir medikal asistan simülasyonudur. Sistem tarafından sağlanan veriler ve dozajlar <strong>kesinlikle profesyonel bir tıbbi teşhis, doktor muayenesi veya tedavi reçetesi yerine geçmez</strong>. Ciddi veya acil sağlık durumlarında derhal en yakın sağlık kuruluşuna başvurmalı veya acil yardım hatlarını aramalısınız.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-user-check text-indigo-500"></i> 3. Kullanıcı Yükümlülükleri
                    </h2>
                    <ul class="list-disc pl-5 space-y-2 mt-2">
                        <li>Kayıt olurken verdiğiniz T.C. Kimlik Numarası, doğum tarihi, ad, soyad ve güvenlik bilgilerinin tamamen doğru olduğunu taahhüt edersiniz.</li>
                        <li>Sistemi kullanırken talep edilen fizyolojik veriler (örneğin kilo) veya özel sağlık durumları (alerjiler, astım, kronik rahatsızlıklar) eksiksiz girilmelidir. Yanlış, yanıltıcı veya eksik sağlık beyanlarından doğabilecek her türlü doğrudan veya dolaylı sağlık riskinden kullanıcı sorumludur.</li>
                        <li>Hesap güvenliğinizin sağlanması ve parolanızın üçüncü şahıslarla paylaşılmaması tamamen sizin yükümlülüğünüzdedir.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-prescription-bottle-medical text-indigo-500"></i> 4. Eczane ve İlaç Onay Süreçleri
                    </h2>
                    <p>Sistem üzerinden hesaplanan dozajlar ve talep edilen medikal ilaçlar otomatik olarak kesinleşmez. İlaç talepleriniz, eşleştiğiniz eczanenin yetkili eczacı paneline ("Onay Paneli") düşer. Eczacı; beyan ettiğiniz tıbbi geçmişinizi, risk faktörlerinizi ve önceki reddedilen ilaç kayıtlarınızı inceleyerek tıbbi değerlendirme yapar. İlacı onaylama veya reddetme konusunda eczacının vereceği medikal karar nihaidir.</p>
                </section>

                <section>
                    <h2 class="text-xl font-bold text-slate-900 mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-indigo-500"></i> 5. Hizmetin Değiştirilmesi
                    </h2>
                    <p>3D Web Doctor ve bağlı tıbbi dolap sistemleri, önceden haber vermeksizin sistem algoritmalarını, sunulan aktif ilaç listelerini, hizmet işleyişini veya işbu Kullanım Şartları'nı değiştirme, askıya alma ya da tamamen sonlandırma hakkını saklı tutar.</p>
                </section>

                <section class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 mt-8">
                    <p class="mb-3 text-slate-700 font-medium">Bu şartlar zaman zaman güncellenebilir. Güncellenmiş şartlar bu sayfada yayınlandığı anda geçerli olur.</p>
                    <p class="text-slate-700">Gizlilik uygulamalarımız için <a href="/privacy" class="text-indigo-600 font-semibold hover:underline">Gizlilik Politikası</a> sayfasını inceleyiniz.</p>
                </section>
            </div>
        </main>
    </div>
</body>
</html>