var questionText = "";
var questionElement = document.getElementById("question");
var optionsContainer = document.getElementById("optionsContainer");
var selectedOptionText = document.getElementById("selectedOptionText");
var doseResult = document.getElementById("doseResult");
var doseForm = document.getElementById("doseForm");
var index = 0;
var typingSpeed = 50;
var lastSelectedOption = null;
var mainOptionSelected = null;

const formatNumber = num => typeof num === 'string' ? num : (typeof num === 'number' && num % 1 !== 0 && num.toString().includes('.') && num.toString().split('.')[1].length > 4) ? parseFloat(num.toFixed(4)) : num;

function getDoseSupplement(medicineStr, amount) {
    let adjustedAmount = parseFloat(amount);
    let finalSupplement = "";

    if (!medicineStr || isNaN(amount)) return [adjustedAmount, finalSupplement];

    if (medicineStr.includes("şurup") || medicineStr.includes("süspansiyon") || medicineStr.includes("çözelti")) {
        if (!medicineStr.includes("saşe")) {
            let ml = adjustedAmount;
            let mgPerMl = 1;
            let hasMg = true;
            if (medicineStr.includes("100 mg/5 mL")) { ml = adjustedAmount / 20; mgPerMl = 20; }
            else if (medicineStr.includes("120 mg/5 mL")) { ml = adjustedAmount / 24; mgPerMl = 24; }
            else if (medicineStr.includes("250 mg/5 mL")) { ml = adjustedAmount / 50; mgPerMl = 50; }
            else if (medicineStr.includes("10 mg/mL")) { ml = adjustedAmount / 10; mgPerMl = 10; }
            else if (medicineStr.includes("1 mg/mL")) { ml = adjustedAmount; mgPerMl = 1; }
            else { hasMg = false; }
            
            let olcek = Math.floor(ml / 2.5) * 0.5;
            if (olcek === 0) {
                finalSupplement = "";
            } else {
                let actualMl = olcek * 5;
                if (hasMg) adjustedAmount = actualMl * mgPerMl;
                else adjustedAmount = actualMl;
                finalSupplement = " (" + olcek + " ölçek)";
            }
        }
    } else if (medicineStr.includes("tablet")) {
        let mgPerTablet = 1;
        if (medicineStr.includes("400 mg")) mgPerTablet = 400;
        else if (medicineStr.includes("600 mg")) mgPerTablet = 600;
        else if (medicineStr.includes("500 mg")) mgPerTablet = 500;
        else if (medicineStr.includes("10 mg")) mgPerTablet = 10;
        
        let count = Math.floor(adjustedAmount / mgPerTablet);
        if (count === 0) {
            finalSupplement = " (0 tablet)";
        } else {
            adjustedAmount = count * mgPerTablet;
            finalSupplement = " (" + count + " tablet)";
        }
    }
    return [adjustedAmount, finalSupplement];
}

let buffer = "";
let recommendation = "";
let hasStartedSpeaking = false;
let discomfort, subDiscomfort, guidance, medicineName, StomachIntensity, temp, res, PainIntensity, doseAmount, doseFrequency, LastMedicine, LastDose, urgency, pharmacy;
let urgent = 0;
let res2 = 0;
let weight = 0;
let selectedBrandObj = null;
let isPharmacyRequired = false;

const catalog = [
    { id: 1, active: "Ibuprofen", brand: "İBUFEN® 100 mg/5 mL şurup", type: "syrup", mgPerMl: 20, group: "Ağrı Sorunları" },
    { id: 2, active: "Ibuprofen", brand: "BRUFEN® 400 mg film kaplı tablet", type: "tablet", mgPerTablet: 400, group: "Ağrı Sorunları" },
    { id: 3, active: "Ibuprofen", brand: "BRUFEN® 600 mg film kaplı tablet", type: "tablet", mgPerTablet: 600, group: "Ağrı Sorunları" },
    { id: 4, active: "Gaviscon", brand: "GAVISCON INFANT® 225 mg/87,5 mg oral çözelti için toz içeren saşe", type: "sachet", group: "Bulantı ve Mide Sorunları" },
    { id: 5, active: "Gaviscon", brand: "GAVISCON® oral süspansiyon", type: "syrup", group: "Bulantı ve Mide Sorunları" },
    { id: 6, active: "Paracetamol", brand: "CALPOL® 120 mg/5 mL süspansiyon", type: "syrup", mgPerMl: 24, group: "Yüksek Ateş ve Yorgunluk / Karın Ağrısı" },
    { id: 7, active: "Paracetamol", brand: "CALPOL® 250 mg/5 mL süspansiyon", type: "syrup", mgPerMl: 50, group: "Yüksek Ateş ve Yorgunluk / Karın Ağrısı" },
    { id: 8, active: "Paracetamol", brand: "PAROL® 500 mg tablet", type: "tablet", mgPerTablet: 500, group: "Yüksek Ateş ve Yorgunluk / Karın Ağrısı" },
    { id: 9, active: "Cetirizine", brand: "ZYRTEC® 10 mg/mL oral damla, çözelti", type: "drop", mgPerMl: 10, group: "Solunum Yolu ve Alerji" },
    { id: 10, active: "Cetirizine", brand: "ZYRTEC® 1 mg/mL şurup", type: "syrup", mgPerMl: 1, group: "Solunum Yolu ve Alerji" },
    { id: 11, active: "Cetirizine", brand: "ZYRTEC® 10 mg film kaplı tablet", type: "tablet", mgPerTablet: 10, group: "Solunum Yolu ve Alerji" }
];

function typeQuestion() {
    var doctorImage = document.getElementById("doctorImage");
    if (index === 0) { 
        buffer = "";
        hasStartedSpeaking = false;
        doctorImage.src = "/images/doctor.gif";
    }

    if (index < questionText.length) {
        const char = questionText.charAt(index);
        questionElement.innerHTML += char;
        buffer += char;
        index++;

        if (!hasStartedSpeaking && char === " ") {
            const utter = new SpeechSynthesisUtterance(questionText.trim());
            utter.rate = 1.75;
            utter.onend = function () {
                doctorImage.src = "/images/doctor.png";
            };
            window.speechSynthesis.speak(utter);
            hasStartedSpeaking = true;
        }

        setTimeout(typeQuestion, typingSpeed);
    }
}

function tempdose(tmp, wgt) {
    doseFrequency = 3;
    let rsd;
    if (wgt > 50) doseFrequency += 1;
    if (urgent >= 0) {
        let tds;
        tds = Math.max(0, 277.7778 * (tmp - 37.2));
        if (tmp < 38.5) rsd = Math.min((tds + 500) * doseFrequency, wgt * 60) / doseFrequency;
        else rsd = Math.max((tds + 500) * doseFrequency, wgt * 60) / doseFrequency;
        if (res2 === 3 && urgent === 0) rsd = Math.min(750, Math.max(500, wgt * 60 / doseFrequency));
        if (tmp < 38.5) {
            if (rsd < 750) rsd = 500;
            else if (rsd < 1000) rsd = 750;
            else rsd = 1000;
        }
    } else rsd = 500;
    
    return rsd;
}

window.onload = function() {
    initWeight();
    document.getElementById("pharmacyBtn").onclick = checkPharmacyCode;
    document.getElementById("pharmacyInput").onkeypress = function(e) { if(e.key === 'Enter') { e.preventDefault(); checkPharmacyCode(); } };
};

function initWeight() {
    questionText = "Kaç kilosunuz?";
    doseForm.classList.remove("hidden");
    var btn = doseForm.querySelector("button");
    if (btn) btn.onclick = submitInitialWeight;
    document.getElementById("weight").onkeypress = function(e) { if(e.key === 'Enter') { e.preventDefault(); submitInitialWeight(); } };
    typeQuestion();
}

function submitInitialWeight() {
    let w = document.getElementById('weight').value.trim();
    if (w === "" || isNaN(w) || parseFloat(w) <= 0) {
        alert("Lütfen geçerli bir kilo giriniz.");
        return;
    }
    weight = parseFloat(w);
    doseForm.classList.add("hidden");
    var btn = doseForm.querySelector("button");
    if (btn) btn.onclick = null;
    showEligibleBrands();
}

function showEligibleBrands() {
    optionsContainer.innerHTML = "";
    
    catalog.forEach(item => {
        let isBrandTaken = recentBrands.some(rb => item.brand.toLowerCase() === rb.toLowerCase());
        if (!isBrandTaken) return;

        if (item.active === "Gaviscon") {
            if (item.id === 4 && age >= 3) return;
            if (item.id === 5 && age < 3) return;
        }

        if (item.active === "Paracetamol" && weight < 5) return;

        let opt = document.createElement("div");
        opt.className = "option";
        opt.innerText = item.brand + " (" + item.group + ")";
        opt.onclick = function() { selectBrand(item, opt); };
        optionsContainer.appendChild(opt);
    });

    if(optionsContainer.innerHTML === "") {
        questionText = "Son 2 yıl içerisinde tarafınızca alınmış veya profilinize uygun bir ilaç bulunamadı.";
    } else {
        questionText = "Elinizde bulunan ilacı seçiniz.";
    }
    index = 0;
    questionElement.innerHTML = '';
    typeQuestion();
}

function getActiveId(active) {
    if(active === "Ibuprofen") return 1;
    if(active === "Gaviscon") return 2;
    if(active === "Paracetamol") return 3;
    if(active === "Cetirizine") return 4;
    return 0;
}

function selectBrand(item, element) {
    applySelectionStyle(element);
    selectedBrandObj = item;
    medicineName = item.active;
    mainOptionSelected = getActiveId(item.active);

    selectedOptionText.innerHTML = "Seçilmiş İlaç Markası: " + item.brand;
    selectedOptionText.style.opacity = 1;

    isPharmacyRequired = requiresPharmacy[medicineName];

    if (isPharmacyRequired) {
        document.getElementById('optionsContainer').style.display = 'none';
        document.getElementById('question').style.display = 'none';
        document.getElementById('pharmacyGate').style.display = 'flex';
    } else {
        pharmacy = "";
        proceedToSymptoms();
    }
}

function checkPharmacyCode() {
    const input = document.getElementById("pharmacyInput").value.trim();
    if (!input) { document.getElementById("pharmacyError").innerText = "Onay kodunu giriniz."; return; }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "check_pharmacy.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            let rs = JSON.parse(xhr.responseText);
            if (rs.status === "ok") {
                pharmacy = rs.pharmacy;
                document.getElementById("pharmacyGate").style.display = "none";
                document.getElementById('optionsContainer').style.display = '';
                document.getElementById('question').style.display = '';
                proceedToSymptoms();
            } else {
                document.getElementById("pharmacyError").innerText = "Onay kodu hatalı.";
            }
        }
    };
    xhr.send("code=" + encodeURIComponent(input) + "&csrf_token=" + encodeURIComponent(csrf_token));
}

function proceedToSymptoms() {
    setTimeout(function() {
        if (mainOptionSelected === 1) {
            askNextQuestionWithOptions("Nereniz Ağrıyor?", ["Baş Ağrısı", "Diş Ağrısı", "Kas Ağrısı"], 1);
        } else if (mainOptionSelected === 2) {
            askNextQuestionWithOptions("Bu belirtilerden herhangi birini yaşadınız mı?", ["Hazımsızlık", "Reflü / Mide Yanması", "Mide Ağrısı", "Mide Bulantısı / Kusma", "Hafif Bir Rahatsızlık"], 2);
        } else if (mainOptionSelected === 3) {
            askNextQuestionWithOptions("Rahatsızlığınız nedir?", ["Karın Ağrısı", "Yüksek Ateş ve Yorgunluk"], 3);
        } else if (mainOptionSelected === 4) {
            askNextQuestionWithOptions("Alerjiniz veya solunum probleminiz var mı?", ["Cilt Alerjisi", "Göz Alerjisi", "Hapşırık ve Burun Tıkanıklığı", "Kuru Öksürük", "Balgamlı Öksürük", "Solunum Yolu Rahatsızlıklar"], 4);
        }
    }, 1000);
}

function askNextQuestionWithOptions(qText, optionsArray, mainOptId) {
    optionsContainer.innerHTML = "";
    optionsArray.forEach(function(optText, idx) {
        var newOption = document.createElement("div");
        newOption.classList.add("option");
        newOption.textContent = optText;
        newOption.onclick = function() {
            handleMainSymptomSelect(mainOptId, idx, optText, newOption);
        };
        optionsContainer.appendChild(newOption);
    });
    askNextQuestionText(qText);
}

function handleMainSymptomSelect(mainOptId, idx, optText, element) {
    applySelectionStyle(element);
    res = " " + optText;
    res2 = idx + 1;
    discomfort = optText;

    if (mainOptId === 1) {
        if (optText === "Kas Ağrısı") res2 = 4;
        selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optText;
        selectedOptionText.style.opacity = 1;
        setTimeout(function() {
            showSubOptionsForPain();
        }, 1000);
    } else if (mainOptId === 2) {
        selectSubOption(2, res2, optText, element);
    } else if (mainOptId === 3) {
        if (idx === 0) {
            res2 = 3;
            selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optText;
            selectedOptionText.style.opacity = 1;
            setTimeout(function() {
                showSubOptionsForPain();
            }, 1000);
        } else {
            res2 = 0;
            selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optText;
            selectedOptionText.style.opacity = 1;
            document.getElementById('optionsContainer').innerHTML = '';
            setTimeout(function() {
                document.getElementById('inputContainerWrapper').style.display = 'flex';
                document.getElementById('tempInput').addEventListener('keypress', function(e) { if(e.key==='Enter'){e.preventDefault(); submittemp();} });
                document.getElementById('submitButton').onclick = submittemp;
                askNextQuestionText("Ateşiniz kaç derecedir?");
            }, 1000);
        }
    } else if (mainOptId === 4) {
        selectSubOption(4, res2, optText, element);
    }
}

function showSubOptionsForPain() {
    var painOptions = ["Keskin", "Devamlı", "Künt/Donuk", "Aralıklı"];
    optionsContainer.innerHTML = "";
    painOptions.forEach(function(optionText, idx) {
        var newOption = document.createElement("div");
        newOption.classList.add("option");
        newOption.textContent = optionText;
        newOption.onclick = function() {
            showGuidanceForPain(idx + 1, optionText, newOption);
        };
        optionsContainer.appendChild(newOption);
    });
    askNextQuestionText("Ağrıyı nasıl tarif edersiniz?");
}

function selectSubOption(mainOption, subOption, optionText, element) {
    if (mainOption === 2) {
        if (subOption === 1 || subOption === 2) { guidance = window.sentences_1; StomachIntensity = 1; }
        else if (subOption === 3 || subOption === 4) { guidance = window.sentences_2; StomachIntensity = 2; }
        else if (subOption === 5) { guidance = window.sentences_1; StomachIntensity = 0; }
    } else if (mainOption === 4) {
        if (subOption > 3) guidance = window.sentences_3; else guidance = window.sentences_4;
        if (subOption === 6) urgency = 4; else if (subOption === 5) urgency = 3; else urgency = 2;
    }
    applySelectionStyle(element);
    subDiscomfort = optionText;
    selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText + "<br>" + guidance;
    selectedOptionText.style.opacity = 1;

    setTimeout(function() {
        calculateDose();
    }, 1000);
}

function showGuidanceForPain(subOption, optionText, element) {
    if (res2 != 3) {
        if (subOption === 1 || subOption === 2) { guidance = window.sentences_5; }
        else if (subOption === 3 || subOption === 4) { guidance = window.sentences_6; }
    } else { 
        temp = 37.2;
        if (subOption === 1 || subOption === 2) { guidance = window.sentences_7; }
        else if (subOption === 3 || subOption === 4) { guidance = window.sentences_8; }
    }
    PainIntensity = 5 - subOption;
    optionText += res;
    subDiscomfort = optionText;
    
    if (mainOptionSelected === 1) {
        if (PainIntensity === 1 || PainIntensity === 2) urgency = 1;
        else if (PainIntensity === 3) urgency = 2;
        else if (PainIntensity === 4) urgency = 3;
    } else if (mainOptionSelected === 3) {
        if (PainIntensity === 1 || PainIntensity === 2) { urgent = -2; urgency = 1; }
        else if (PainIntensity === 3) { urgent = -1; urgency = 2; }
        else if (PainIntensity === 4) { urgent = 0; urgency = 3; }
    }

    applySelectionStyle(element);
    selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText + "<br>" + guidance;
    selectedOptionText.style.opacity = 1;

    setTimeout(function() {
        calculateDose();
    }, 1000);
}

function submittemp() {
    const inputElement = document.getElementById('tempInput');
    let rawTemp = inputElement.value.trim();
    if (rawTemp === "") { alert('Lütfen bir ateş derecesi giriniz.'); return; }
    temp = parseFloat(rawTemp);
    if (temp >= 36.1 && temp <= 47) {
        document.getElementById('inputContainerWrapper').style.display = 'none';
        
        if (temp <= 37.2) {
            document.getElementById('optionsContainer').style.display = 'none';
            document.getElementById('question').style.display = 'none';
            const messageDiv = document.createElement('div');
            messageDiv.id = 'redirect-message';
            messageDiv.style.color = 'orange';
            messageDiv.style.fontSize = '16px';
            messageDiv.style.fontWeight = 'bold';
            messageDiv.style.margin = 'auto';
            messageDiv.style.textAlign = 'center';
            messageDiv.style.width = 'fit-content';
            document.body.appendChild(messageDiv);
            startCountdown(5, "Ateşiniz normaldir yüksek değildir dolaysıyla {time} saniye içinde ana sayfaya yönlendirliyorsunuz.", "/");
            return;
        }

        var tempOptions = ["Titreme", "Kilo Kaybı", "Vücut Ağrısı", "Uykuya Meyil", "Hafif Bir Rahatsızlık"];
        optionsContainer.innerHTML = "";
        tempOptions.forEach(function(optText, idx) {
            var newOption = document.createElement("div");
            newOption.classList.add("option");
            newOption.textContent = optText;
            newOption.onclick = function() { showGuidanceForTemp(idx + 1, optText, newOption); };
            optionsContainer.appendChild(newOption);
        });
        askNextQuestionText("Başlıca belirtileriniz nelerdir?");
    } else {
        alert('Lütfen geçerli bir ateş derecesi giriniz.');
    }
}

function showGuidanceForTemp(subOption, optionText, element) {
    if (temp >= 39 || subOption === 4) { guidance = window.sentences_9; urgent = 1; urgency = 4; }
    else if (subOption === 1 || subOption === 3) { guidance = window.sentences_10; urgency = 2; }
    else if (subOption === 2) { guidance = window.sentences_11; urgency = 3; }
    else if (subOption === 5) {
        if (temp <= 37.2) { guidance = window.sentences_12; urgent = -2; urgency = 1; }
        else if (temp < 37.5) { guidance = window.sentences_12; urgent = -1; urgency = 2; }
        else { guidance = window.sentences_10; urgency = 2; }
    }
    discomfort = "Ateş " + temp + "°C";
    optionText = temp + "°C " + optionText;
    subDiscomfort = optionText;
    applySelectionStyle(element);
    selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText + "<br>" + guidance;
    selectedOptionText.style.opacity = 1;

    setTimeout(function() {
        calculateDose();
    }, 1000);
}

function askNextQuestionText(qText) {
    questionText = qText;
    index = 0;
    questionElement.innerHTML = '';
    typeQuestion();
}

function applySelectionStyle(selectedOption) {
    if (lastSelectedOption !== null) lastSelectedOption.classList.remove("selected");
    selectedOption.classList.add("selected");
    lastSelectedOption = selectedOption;
}

function startCountdown(duration, messageTemplate, redirectUrl) {
    const messageDiv = document.getElementById('redirect-message');
    if (!messageDiv) return;
    let timeLeft = duration;
    messageDiv.textContent = messageTemplate.replace('{time}', timeLeft);
    const interval = setInterval(function() {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(interval);
            window.location.href = redirectUrl;
        } else {
            messageDiv.textContent = messageTemplate.replace('{time}', timeLeft);
        }
    }, 1000);
}

function startStatusPolling(recordId) {
    const messageDiv = document.getElementById('redirect-message');
    if (!messageDiv) return;

    const pollingInterval = setInterval(function () {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "check_status.php?id=" + encodeURIComponent(recordId), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var rawText = xhr.responseText.replace(/^\uFEFF/, '').trim();
                    var response = JSON.parse(rawText);
                    
                    if (response.result === '+') {
                        clearInterval(pollingInterval);
                        let det = response.detail_conclusion_rationale || "";
                        let arr = [];
                        if (det) arr.push(det);
                        let combinedText = "(" + arr.join(" ") + ") ";
                        let msg = "İlacınız, bu " + combinedText + "gerekçelerinden dolayı kabul edilmiştir. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz...";
                        startCountdown(5, msg, "/resume");
                    } else if (response.result === '-') {
                        clearInterval(pollingInterval);
                        let conc = response.conclusion_rationale || "";
                        let det = response.detail_conclusion_rationale || "";
                        let arr = [];
                        if (det) arr.push(det);
                        if (conc) arr.push(conc);
                        let combinedText = "(" + arr.join(" ") + ") ";
                        let msg = "İlacınız, bu " + combinedText + "gerekçelerinden dolayı reddedilmiştir. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz...";
                        startCountdown(5, msg, "/resume");
                    }
                } catch (e) {
                }
            }
        };
        xhr.send();
    }, 2000);
}

function calculateDose() {
    doseResult.innerHTML += "Mevcut Hasta Bilgisi: " + customAge + " " + weight + " kilo.<br><br>";

    if (mainOptionSelected === 1) {
        medicineName = "Ibuprofen";
        LastMedicine = selectedBrandObj.brand;
        if (age < 18) {
            doseAmount = weight * 6;
            if (doseAmount > 400) { doseAmount = 400; }
            if (PainIntensity === 1 || PainIntensity === 2) doseFrequency = 3;
            else if (PainIntensity === 3 || PainIntensity === 4) doseFrequency = 4;
        } else {
            if (PainIntensity === 1) { doseFrequency = 2; doseAmount = 400; }
            else if (PainIntensity === 2) { doseFrequency = 3; doseAmount = 400; }
            else if (PainIntensity === 3) { doseFrequency = 4; doseAmount = 400; }
            else if (PainIntensity === 4) { doseFrequency = 4; doseAmount = 600; }
        }
        let doseData = getDoseSupplement(LastMedicine, doseAmount);
        doseAmount = doseData[0];
        let supplement = doseData[1];
        
        doseResult.innerHTML += "Günlük Ibuprofen Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınmalıdır.";
        LastDose = formatNumber(doseAmount) + " mg" + supplement;
           
    } else if (mainOptionSelected === 2) {
        if (selectedBrandObj.id === 4) {
             medicineName = "Gaviscon Infant";
             LastMedicine = selectedBrandObj.brand;
             doseFrequency = "≤6";
             doseResult.innerHTML += "Yaşınız 3 yaştan küçük. Bu ilacı doktor gözetiminde alın. Gaviscon Infant'ı kullanmanız önerilir.<br><br>Biberonla besleme:<br>Her poşeti 115 mL biberonla karıştırın. İyice çalkalayın ve bebeği normal şekilde besleyin. Beslenme bittikten sonra Gaviscon Infant'ı bir kaşık veya şişe kullanarak uygulayın.<br><br>Emzirme:<br>Her poşeti bir çay kaşığı kaynamış ancak soğutulmuş suyla karıştırın. Pürüzsüz bir macun oluşacaktır. 2 çay kaşığı kaynamış, sarmal su ekleyin ve karıştırın. Mamanın yarısında, uygulamak için bir kaşık veya biberon kullanın.<br><br>";
             recommendation = "Yaşınız 3 yaştan küçük. Bu ilacı doktor gözetiminde alın. Gaviscon Infant'ı kullanmanız önerilir. Biberonla besleme: Her poşeti 115 mL biberonla karıştırın. İyice çalkalayın ve bebeği normal şekilde besleyin. Beslenme bittikten sonra Gaviscon Infant'ı bir kaşık veya şişe kullanarak uygulayın. Emzirme: Her poşeti bir çay kaşığı kaynamış ancak soğutulmuş suyla karıştırın. Pürüzsüz bir macun oluşacaktır. 2 çay kaşığı kaynamış, sarmal su ekleyin ve karıştırın. Mamanın yarısında, uygulamak için bir kaşık veya biberon kullanın.";
             if (weight < 4.5){
                 doseAmount = 115;
                 doseResult.innerHTML += "Günlük Gaviscon Infant Poşet Dozu: 1 Poşet, Günde en fazla 6 kez alınmalıdır.";
             } else {
                 doseAmount = 230;
                 doseResult.innerHTML += "Günlük Gaviscon Infant Poşet Dozu: 2 Poşet, Günde en fazla 6 kez alınmalıdır.";
             }
             urgency = 4;
             LastDose = formatNumber(doseAmount) + (doseAmount === 115 ? " (1 Poşet)" : " (2 Poşet)");
        } else {
              medicineName = "Gaviscon";
              LastMedicine = selectedBrandObj.brand;
              doseFrequency = 4;
              if (age < 6) {
                doseAmount = 5 * (age - 2) / 8 + 2.5;   
                if (StomachIntensity === 0) doseAmount = 2.5;
                urgency = Math.max(StomachIntensity + 1, 2);
              } else if (age <= 18) { // exceptional
                doseAmount = 5 * age / 6;
                if (StomachIntensity === 0 && age < 12) doseAmount = 5;
                else if (StomachIntensity === 0) doseAmount = 10;
                urgency = StomachIntensity + 1;
              } else {
                if (StomachIntensity === 0) doseAmount = 10;
                else if (StomachIntensity === 1) doseAmount = 15;
                else if (StomachIntensity === 2) doseAmount = 20;
                urgency = StomachIntensity + 1;
              }

              let doseData = getDoseSupplement(LastMedicine, doseAmount);
              doseAmount = doseData[0];
              let supplement = doseData[1];
              
              if (age < 6) {
                  doseResult.innerHTML += "Yaşınız 6 yaştan küçük. Bu ilacı doktor gözetiminde alın.<br>Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " mL" + supplement + ", Günde 4 kez alınmalıdır.";
                  recommendation = "Yaşınız 6 yaştan küçük. Bu ilacı doktor gözetiminde alın.";
              } else {
                  doseResult.innerHTML += "Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " mL" + supplement + ", Günde 4 kez alınmalıdır.";
              }
              LastDose = formatNumber(doseAmount) + " mL" + supplement;
        }
        
    } else if (mainOptionSelected === 3) {
        medicineName = "Paracetamol";
        LastMedicine = selectedBrandObj.brand;
        if (age < 18) {
            if (urgent == -2) { doseAmount = weight * 10; doseFrequency = 4; }
            else { doseAmount = weight * 15; doseFrequency = 4; }
        } else if (weight < 40) { doseAmount = 500; doseFrequency = 2; }
        else doseAmount = tempdose(temp, weight);
        
        doseAmount = Math.min(doseAmount, 1000);
        if (urgent < 0) doseAmount = Math.min(doseAmount, 500);
        
        let doseData = getDoseSupplement(LastMedicine, doseAmount);
        doseAmount = doseData[0];
        let supplement = doseData[1];
        
        if (urgent == 1) { doseResult.innerHTML += "Acil Alınacak Paracetamol Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınabilir."; }
        else { doseResult.innerHTML += "Günlük Paracetamol Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınmalıdır."; }
        
        LastDose = formatNumber(doseAmount) + " mg" + supplement;
        
    } else if (mainOptionSelected === 4) {
        medicineName = "Cetirizine";
        LastMedicine = selectedBrandObj.brand;
        doseFrequency = 1;
        if (age < 2) { doseAmount = 2.5; }
        else if (age >= 2 && age < 6) { doseAmount = 2.5 + (age - 2) * 0.625; }
        else if (age >= 6 && age < 12) { doseAmount = 5 + (age - 6) * 0.833; }
        else { doseAmount = 10; }
        
        let doseData = getDoseSupplement(LastMedicine, doseAmount);
        doseAmount = doseData[0];
        let supplement = doseData[1];
        
        doseResult.innerHTML += "Günlük Cetirizine Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde 1 kez alınmalıdır.";
        LastDose = formatNumber(doseAmount) + " mg" + supplement;
    }
    
    if (age < 3 || age >= 75) urgency = 4;
    else if (age < 6 || age >= 65) urgency = Math.max(urgency, 3);

    let result = "";
    let conclusion_rationale = "";
    let isRejected = false;

    document.getElementById('optionsContainer').style.display = 'none';
    document.getElementById('question').style.display = 'none';

    setTimeout(function() {
        let infoLower = String(information || "").replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase();
        let medLower = String(medicineName || "").replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase();
        let isRejectedActive = rejectedActives.some(a => a.toLowerCase() === medLower);
        let infoRejected = false;

        if (infoLower.includes(medLower)) infoRejected = true;
        else if (medLower.includes("ibuprofen") && infoLower.includes("ibuprofen")) infoRejected = true;
        else if (medLower.includes("paracetamol") && infoLower.includes("paracetamol")) infoRejected = true;
        else if (medLower.includes("gaviscon") && infoLower.includes("gaviscon")) infoRejected = true;
        else if (medLower.includes("cetirizine") && infoLower.includes("cetirizine")) infoRejected = true;

        let isDoseTooLow = LastDose.includes("(0 tablet)");

        if (isDoseTooLow) {
            result = "-";
            conclusion_rationale = "İstenilen doz, seçilen markanın en düşük dozundan daha düşük olduğu için kullanıma uygun görülmemiştir.";
            isRejected = true;
        } else if (isRejectedActive || infoRejected) {
            result = "-";
            conclusion_rationale = "Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır.";
            isRejected = true;
        } else {
            if (isPharmacyRequired) {
                result = "";
                conclusion_rationale = "";
            } else {
                result = "+";
                conclusion_rationale = "Daha önceden uygun görülen ilgili ilaç tekrardan uygun görüldü.";
            }
        }

        const messageDiv = document.createElement('div');
        messageDiv.id = 'redirect-message';
        messageDiv.style.color = 'orange';
        messageDiv.style.fontSize = '16px';
        messageDiv.style.fontWeight = 'bold';
        messageDiv.style.margin = 'auto';
        messageDiv.style.textAlign = 'center';
        messageDiv.style.width = 'fit-content';
        document.body.appendChild(messageDiv);

        if (!isRejected && isPharmacyRequired) {
            messageDiv.textContent = "Merhaba, " + name + " " + surname + " ilacınız onaylandığında yönlendirileceksiniz.";
        } else {
            messageDiv.textContent = "İşlem kaydediliyor...";
        }

        var xhr = new XMLHttpRequest();
        xhr.open("POST", "save_dose.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.last_id) {
                    if (isRejected) {
                        let combinedText = "(" + conclusion_rationale + ") ";
                        startCountdown(5, "İlacınız, bu " + combinedText + "gerekçelerinden dolayı reddedilmiştir. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz...", "/resume");
                    } else if (result === '+') {
                        let combinedText = "(" + conclusion_rationale + ") ";
                        startCountdown(5, "İlacınız, bu " + combinedText + "gerekçesiyle sistem tarafından otomatik onaylanmıştır. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz...", "/resume");
                    } else {
                        startStatusPolling(response.last_id);
                    }
                }
            }
        };
        var data = "user_id=" + user_id + "&medicine=" + encodeURIComponent(medicineName) + "&brand=" + encodeURIComponent(LastMedicine) + "&name=" + encodeURIComponent(name) + "&surname=" + encodeURIComponent(surname) + "&information=" + encodeURIComponent(information) + "&age=" + encodeURIComponent(customAge) + "&weight=" + weight + "&dose=" + encodeURIComponent(LastDose) + "&dailyAmount=" + encodeURIComponent(formatNumber(doseFrequency)) + "&discomfort=" + encodeURIComponent(discomfort) + "&subdiscomfort=" + encodeURIComponent(subDiscomfort) + "&recommendation=" + encodeURIComponent(recommendation) + "&guidance=" + encodeURIComponent(guidance) + "&urgency=" + encodeURIComponent(urgency) + "&pharmacy=" + encodeURIComponent(pharmacy) + "&types=" + encodeURIComponent("ev") + "&conclusion_rationale=" + encodeURIComponent(conclusion_rationale) + "&result=" + encodeURIComponent(result) + "&csrf_token=" + encodeURIComponent(csrf_token);
        xhr.send(data);
    }, 300);
}