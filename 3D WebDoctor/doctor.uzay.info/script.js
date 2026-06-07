var questionText = "Rahatsızlık Belirtiniz Nedir?";
var questionElement = document.getElementById("question");
var optionsContainer = document.getElementById("optionsContainer");
var selectedOptionText = document.getElementById("selectedOptionText");
var doseForm = document.getElementById("doseForm");
var doseResult = document.getElementById("doseResult");
var index = 0;
var typingSpeed = 50;
var changeTimer;
var lastSelectedOption = null;
var mainOptionSelected = null;

const formatNumber = num => typeof num === 'string' ? num : (typeof num === 'number' && num % 1 !== 0 && num.toString().includes('.') && num.toString().split('.')[1].length > 4) ? parseFloat(num.toFixed(4)) : num;

function getDoseSupplement(medicineStr, amount) {
    if (!medicineStr || isNaN(amount) || amount === "NotApproved" || amount === "NotRecommended") return "";
    if (medicineStr.includes("şurup") || medicineStr.includes("süspansiyon")) {
        let ml = parseFloat(amount);
        if (medicineStr.includes("100 mg/5 mL")) ml = ml / 20; 
        else if (medicineStr.includes("120 mg/5 mL")) ml = ml / 24;
        else if (medicineStr.includes("250 mg/5 mL")) ml = ml / 50;
        else if (medicineStr.includes("1 mg/mL")) ml = ml;
        
        let olcek = Math.floor(ml / 2.5) * 0.5;
        if (olcek > 0) return " (" + olcek + " ölçek)";
        return "";
    } else if (medicineStr.includes("tablet")) {
        let count = 0;
        let mg = parseFloat(amount);
        if (medicineStr.includes("400 mg")) count = mg / 400;
        else if (medicineStr.includes("600 mg")) count = mg / 600;
        else if (medicineStr.includes("500 mg")) count = mg / 500;
        else if (medicineStr.includes("10 mg")) count = mg / 10;
        
        if (count > 0) return " (" + formatNumber(count) + " tablet)";
        return "";
    }
    return "";
}

let buffer = "";
let hasStartedSpeaking = false;

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

let discomfort, subDiscomfort, guidance, medicineName, StomachIntensity, temp, res, PainIntensity, doseAmount, doseFrequency, LastMedicine, LastDose, urgency, pharmacy;
let recommendation = "";
let urgent = 0;
let res2 = 0;

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

function selectOption(optionNumber, optionText) {
    clearTimeout(changeTimer);

    var option = event.target;
    mainOptionSelected = optionNumber;

    if (option !== lastSelectedOption) {
        if (lastSelectedOption !== null) {
            lastSelectedOption.classList.remove("selected");
        }
        option.classList.add("selected");
        selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText;
        discomfort = optionText;
        selectedOptionText.style.opacity = 1;
        lastSelectedOption = option;

        changeTimer = setTimeout(function() {
            updateOptions(optionNumber);
            askNextQuestion(optionNumber);
        }, 1000);
    } else {
        option.classList.remove("selected");
        selectedOptionText.innerHTML = "Seçilen Rahatsızlık Belirtisi:";
        lastSelectedOption = null;
    }
}

function selectSubOption(mainOption, subOption, optionText) {
    switch (mainOption) {
        case 1:
            askNextQuestion(5);
            updateOptionsForPain();
            return;
        case 2:
            if (subOption === 1 || subOption === 2) {
                guidance = window.sentences_1;
                StomachIntensity = 1;
            } else if (subOption === 3 || subOption === 4) {
                guidance = window.sentences_2;
                StomachIntensity = 2;
            } else if (subOption === 5) {
                guidance = window.sentences_1;
                StomachIntensity = 0;
            }
            showDoseForm();
            break;
        case 3:
            askNextQuestion(6);
            updateOptionsForTemp();   
            break;
        case 4:
            if (subOption > 3) guidance = window.sentences_3;
            else guidance = window.sentences_4;
            if (subOption === 6) urgency = 4;
            else if (subOption === 5) urgency = 3;
            else  urgency = 2;
            showDoseForm();
            break;
        default:
            break;
    }
    applySelectionStyle(event.target);
    subDiscomfort = optionText;
    selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText + "<br>" + guidance;
    selectedOptionText.style.opacity = 1;
}

function nexttemp() { 
    askNextQuestion(6);
    updateOptionsForTemp();     
}

function submittemp() {
    const inputElement = document.getElementById('tempInput');
    let rawTemp = inputElement.value.trim();
    if (rawTemp === "") {
        alert('Lütfen bir ateş derecesi giriniz.');
        return;
    }
    temp = parseFloat(rawTemp);
    if (temp >= 36.1 && temp <= 47) {
        document.getElementById('inputContainer').style.display = 'none';
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

        nexttemp();
    } else {
        alert('Lütfen geçerli bir ateş derecesi giriniz.');
    }
}

function updateOptions(optionNumber) {
    var newOptions = [];
    switch (optionNumber) {
        case 1:
            newOptions = ["Baş Ağrısı", "Diş Ağrısı", "Karın Ağrısı", "Kas Ağrısı"];
            break;
        case 2:
            newOptions = ["Hazımsızlık", "Reflü / Mide Yanması", "Mide Ağrısı", "Mide Bulantısı / Kusma", "Hafif Bir Rahatsızlık"];
            break;
        case 3:
            document.getElementById('inputContainerWrapper').style.display = 'flex';
            document.getElementById('tempInput').addEventListener('keypress', function(event) { if (event.key === 'Enter') { event.preventDefault(); submittemp(); } });
            document.getElementById('submitButton').addEventListener('click', function() { submittemp(); });
            lastSelectedOption = null;
            break;
        case 4:
            newOptions = ["Cilt Alerjisi", "Göz Alerjisi", "Hapşırık ve Burun Tıkanıklığı", "Kuru Öksürük", "Balgamlı Öksürük", "Solunum Yolu Rahatsızlıklar"];
            break;
        default:
            break;
    }

    optionsContainer.innerHTML = "";
    newOptions.forEach(function(optionText, index) {
        var newOption = document.createElement("div");
        newOption.classList.add("option");
        newOption.textContent = optionText;
        newOption.onclick = function() {
            res = " " + optionText;
            res2 = index + 1;
            selectSubOption(optionNumber, index + 1, optionText);
        };
        optionsContainer.appendChild(newOption);
    });

    lastSelectedOption = null;
}

function updateOptionsForPain() {
    var painOptions = ["Keskin", "Devamlı", "Künt/Donuk", "Aralıklı"];
    optionsContainer.innerHTML = "";
    painOptions.forEach(function(optionText, index) {
        var newOption = document.createElement("div");
        newOption.classList.add("option");
        newOption.textContent = optionText;
        newOption.onclick = function() {
            showGuidanceForPain(index + 1, optionText);
        };
        optionsContainer.appendChild(newOption);
    });

    lastSelectedOption = null;
}

function showGuidanceForPain(subOption, optionText) {
    if (res2 != 3) {
        if (subOption === 1 || subOption === 2) { guidance = window.sentences_5; }
        else if (subOption === 3 || subOption === 4) { guidance = window.sentences_6; }
    } else { 
        temp = 37.2;
        if (subOption === 1 || subOption === 2) { guidance = window.sentences_7; }
        else if (subOption === 3 || subOption === 4) {  guidance = window.sentences_8; }
    }
    
    PainIntensity = 5 - subOption;
    showDoseForm();
    
    optionText +=  res;
    subDiscomfort = optionText;
    applySelectionStyle(event.target);
    selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText + "<br>" + guidance;
    selectedOptionText.style.opacity = 1;
}

function updateOptionsForTemp() {
    var tempOptions = ["Titreme", "Kilo Kaybı", "Vücut Ağrısı", "Uykuya Meyil", "Hafif Bir Rahatsızlık"];
    optionsContainer.innerHTML = "";
    tempOptions.forEach(function(optionText, index) {
        var newOption = document.createElement("div");
        newOption.classList.add("option");
        newOption.textContent = optionText;
        newOption.onclick = function() {
            showGuidanceForTemp(index + 1, optionText);
        };
        optionsContainer.appendChild(newOption);
    });

    lastSelectedOption = null;
}

function showGuidanceForTemp(subOption, optionText) {
    if (temp >= 39 || subOption === 4) { 
        guidance = window.sentences_9;
        urgent = 1;
        urgency = 4;
    } else if (subOption === 1 || subOption === 3) {
        guidance = window.sentences_10;
        urgency = 2;
    } else if (subOption === 2) {
        guidance = window.sentences_11;
        urgency = 3;
    } else if (subOption === 5) {
        if (temp <= 37.2) {
            guidance = window.sentences_12;
            urgent = -2;
            urgency = 1;
        } else if (temp < 37.5) {
            guidance = window.sentences_12;
            urgent = -1;
            urgency = 2;
        } else { 
            guidance = window.sentences_10; 
            urgency = 2;
        }
    }
    showDoseForm();
    optionText = temp + "°C " + optionText;
    subDiscomfort = optionText;
    applySelectionStyle(event.target);
    selectedOptionText.innerHTML = "Seçilmiş Rahatsızlık Belirtisi: " + optionText + "<br>" + guidance;
    selectedOptionText.style.opacity = 1;
}

function askNextQuestion(optionNumber) {
    var nextQuestionText = "";
    switch (optionNumber) {
        case 1:
            nextQuestionText = "Nereniz Ağrıyor?";
            break;
        case 2:
            nextQuestionText = "Bu belirtilerden herhangi birini yaşadınız mı?";
            break;
        case 3:
            nextQuestionText = "Ateşiniz kaç derecedir?";
            break;
        case 4:
            nextQuestionText = "Alerjiniz veya solunum probleminiz var mı?";
            break;
        case 5:
            nextQuestionText = "Ağrıyı nasıl tarif edersiniz?";
            break;
        case 6:
            nextQuestionText = "Başlıca belirtileriniz nelerdir?";
        default:
            break;
    }
    index = 0;
    questionText = nextQuestionText;
    questionElement.innerHTML = '';
    setTimeout(typeQuestion, 1500);
}

function applySelectionStyle(selectedOption) {
    if (lastSelectedOption !== null) {
        lastSelectedOption.classList.remove("selected");
    }
    selectedOption.classList.add("selected");
    lastSelectedOption = selectedOption;
}

function showDoseForm() { 
    doseForm.classList.remove("hidden"); 
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
                        let msg = "İlacınız, bu " + combinedText + "gerekçelerinden dolayı kabul edilmiştir. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz..."
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
                    console.error("Durum kontrolü ayrıştırılamadı:", e);
                }
            }
        };
        xhr.send();
    }, 2000);
}

function calculateDose() {
    var weightInputRaw = document.getElementById("weight").value.trim();
    if (weightInputRaw === "" || isNaN(weightInputRaw) || parseFloat(weightInputRaw) <= 0) {
        alert("Lütfen geçerli bir kilo giriniz.");
        return;
    }
    
    var weight = parseFloat(weightInputRaw).toFixed(2);
    doseResult.innerHTML += "Mevcut Hasta Bilgisi: " + customAge + " " + weight + " kilo.<br><br>";

    if (mainOptionSelected === 1) {
        medicineName = "Ibuprofen";
        
        if (res2 == 3) {
            mainOptionSelected = 3;
            doseResult.innerHTML = "";
            if (PainIntensity === 1 || PainIntensity === 2) { urgent = -2; urgency = 1; }
            else if (PainIntensity === 3) { urgent = -1; urgency = 2; }
            else if (PainIntensity === 4) { urgent = 0; urgency = 3; }
            calculateDose();
            return;
        } else if (age < 0.25) {
             doseAmount = "NotApproved";
             doseFrequency = "NotApproved";
             doseResult.innerHTML += "Yaşınız 3 aydan küçük. Ibuprofen için uzmanınıza danışın.";
             recommendation = "Yaşınız 3 aydan küçük. Ibuprofen için uzmanınıza danışın.";
             
        } else if (age < 18) {
            doseAmount = weight * 6;
            LastMedicine = "İBUFEN® 100 mg/5 mL şurup";
            if (doseAmount > 400) {doseAmount = 400; LastMedicine = "BRUFEN® 400 mg film kaplı tablet";}
            
            let supplement = getDoseSupplement(LastMedicine, doseAmount);
            if (PainIntensity === 1 || PainIntensity === 2) doseFrequency = 3;
            else if (PainIntensity === 3 || PainIntensity === 4) doseFrequency = 4;
            doseResult.innerHTML += "Günlük Ibuprofen Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınmalıdır.";
            
        } else {
            if (PainIntensity === 1) {doseFrequency = 2; doseAmount = 400; LastMedicine = "BRUFEN® 400 mg film kaplı tablet";}
            else if (PainIntensity === 2) {doseFrequency = 3; doseAmount = 400; LastMedicine = "BRUFEN® 400 mg film kaplı tablet";}
            else if (PainIntensity === 3) {doseFrequency = 4; doseAmount = 400; LastMedicine = "BRUFEN® 400 mg film kaplı tablet";}
            else if (PainIntensity === 4) {doseFrequency = 4; doseAmount = 600; LastMedicine = "BRUFEN® 600 mg film kaplı tablet";}
            
            let supplement = getDoseSupplement(LastMedicine, doseAmount);
            doseResult.innerHTML += "Günlük Ibuprofen Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınmalıdır.";
        }
        
        if (PainIntensity === 1 || PainIntensity === 2) urgency = 1;
        else if (PainIntensity === 3) urgency = 2;
        else if (PainIntensity === 4) urgency = 3;
        
        LastDose = formatNumber(doseAmount) + " mg" + getDoseSupplement(LastMedicine, doseAmount);
           
    } else if (mainOptionSelected === 2) {
        medicineName = "Gaviscon Infant";
        LastMedicine = "GAVISCON INFANT® 225 mg/87,5 mg oral çözelti için toz içeren saşe";
        if (age < 1) {
             doseAmount = "NotRecommended";
             doseFrequency = "NotRecommended";
             doseResult.innerHTML += "Yaşınız 1 yaştan küçük. Gaviscon ve Gaviscon Infant'ı kullanmanız önerilmez. Lütfen ilaç için uzmanınıza danışınız.";
             recommendation = "Yaşınız 1 yaştan küçük. Gaviscon ve Gaviscon Infant'ı kullanmanız önerilmez. Lütfen ilaç için uzmanınıza danışınız.";
         
        } else if (age < 3) {
             doseFrequency = "≤6";
             doseResult.innerHTML += "Yaşınız 3 yaştan küçük. Bu ilacı doktor gözetiminde alın. Gaviscon Infant'ı kullanmanız önerilir.<br><br>Biberonla besleme:<br>Her poşeti 115 mL biberonla karıştırın. İyice çalkalayın ve bebeği normal şekilde besleyin. Beslenme bittikten sonra Gaviscon Infant'ı bir kaşık veya şişe kullanarak uygulayın.<br><br>Emzirme:<br>Her poşeti bir çay kaşığı kaynamış ancak soğutulmuş suyla karıştırın. Pürüzsüz bir macun oluşacaktır. 2 çay kaşığı kaynamış, sarmal su ekleyin ve karıştırın. Mamanın yarısında, uygulamak için bir kaşık veya biberon kullanın.<br><br>";
             recommendation = "Yaşınız 3 yaştan küçük. Bu ilacı doktor gözetiminde alın. Gaviscon Infant'ı kullanmanız önerilir. Biberonla besleme: Her poşeti 115 mL biberonla karıştırın. İyice çalkalayın ve bebeği normal şekilde besleyin. Beslenme bittikten sonra Gaviscon Infant'ı bir kaşık veya şişe kullanarak uygulayın. Emzirme: Her poşeti bir çay kaşığı kaynamış ancak soğutulmuş suyla karıştırın. Pürüzsüz bir macun oluşacaktır. 2 çay kaşığı kaynamış, sarmal su ekleyin ve karıştırın. Mamanın yarısında, uygulamak için bir kaşık veya biberon kullanın.";
         
             if (weight < 4.5){
                 doseAmount = 115;
                 doseResult.innerHTML += "Günlük Gaviscon Infant Poşet Dozu: 1 Poşet, Günde en fazla 6 kez alınmalıdır.";
             } else {
                 doseAmount = 230;
                 doseResult.innerHTML += "Günlük Gaviscon Infant Poşet Dozu: 2 Poşet, Günde en fazla 6 kez alınmalıdır";
             }
         
             urgency = 4;
         
        } else {
              medicineName = "Gaviscon";
              LastMedicine = "GAVISCON® oral süspansiyon";
              doseFrequency = 4;
              if (age < 6) {
                doseAmount = 5 * (age - 2) / 8 + 2.5;   
                if (StomachIntensity === 0) doseAmount = 2.5;
                
                let supplement = getDoseSupplement(LastMedicine, doseAmount);
                doseResult.innerHTML += "Yaşınız 6 yaştan küçük. Bu ilacı doktor gözetiminde alın.<br>Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " mL" + supplement + ", Günde 4 kez alınmalıdır.";
                recommendation = "Yaşınız 6 yaştan küçük. Bu ilacı doktor gözetiminde alın.";
                urgency = Math.max(StomachIntensity + 1, 2);
                  
            } else if (age <= 18) { // exceptional
                doseAmount = 5 * age / 6;
                if (StomachIntensity === 0 && age < 12) doseAmount = 5;
                else if (StomachIntensity === 0) doseAmount = 10;
                
                let supplement = getDoseSupplement(LastMedicine, doseAmount);
                doseResult.innerHTML += "Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " mL" + supplement + ", Günde 4 kez alınmalıdır.";
                urgency = StomachIntensity + 1;
                  
            } else {
                if (StomachIntensity === 0) doseAmount = 10;
                else if (StomachIntensity === 1) doseAmount = 15;
                else if (StomachIntensity === 2) doseAmount = 20;
                
                let supplement = getDoseSupplement(LastMedicine, doseAmount);
                doseResult.innerHTML += "Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " mL" + supplement + ", Günde 4 kez alınmalıdır.";
                urgency = StomachIntensity + 1;
            }
        }
        
        LastDose = formatNumber(doseAmount) + " mL" + getDoseSupplement(LastMedicine, doseAmount);
        
    } else if (mainOptionSelected === 3) {
        medicineName = "Paracetamol";
        
        if (age < 0.0833) {
            doseAmount = "NotApproved";
            doseFrequency = "NotApproved";
            doseResult.innerHTML += "Yaşınız 1 aydan küçük. Paracetamol için uzmanınıza danışın."; 
            recommendation = "Yaşınız 1 aydan küçük. Paracetamol için uzmanınıza danışın."; 
            
        } else if (age < 18) {
            if (weight < 5) {
                doseAmount = "NotApproved";
                doseFrequency = "NotApproved";
                doseResult.innerHTML += "Kilonuz çok düşük. Paracetamol için uzmanınıza danışın.";
                recommendation = "Kilonuz çok düşük. Paracetamol için uzmanınıza danışın."; 
            } else if (urgent == -2) { doseAmount = weight * 10; doseFrequency = 4; }
            else { doseAmount = weight * 15; doseFrequency = 4; }
        } else if (weight < 40) { doseAmount = 500; doseFrequency = 2; }
        else doseAmount = tempdose(temp, weight);
        
        if (age >= 0.0833 && weight >= 5) {
            doseAmount = Math.min(doseAmount, 1000);
            if (urgent < 0) doseAmount = Math.min(doseAmount, 500);
        }
        
        if (doseAmount == 500 || doseAmount == 1000) LastMedicine = "PAROL® 500 mg tablet";
        else if (age < 6) LastMedicine = "CALPOL® 120 mg/5 mL süspansiyon";
        else LastMedicine = "CALPOL® 250 mg/5 mL süspansiyon";

        if (age >= 0.0833 && weight >= 5) {
            let supplement = getDoseSupplement(LastMedicine, doseAmount);
            if (urgent == 1) { doseResult.innerHTML += "Acil Alınacak Paracetamol Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınabilir."; }
            else { doseResult.innerHTML += "Günlük Paracetamol Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde " + doseFrequency + " kez alınmalıdır."; }
        }
        
        LastDose = formatNumber(doseAmount) + " mg" + getDoseSupplement(LastMedicine, doseAmount);
        
    } else if (mainOptionSelected === 4) {
        
        medicineName = "Cetirizine";
        doseFrequency = 1;
        
        if (age < 2) {
            doseAmount = "NotRecommended";
            doseFrequency = "NotRecommended";
            doseResult.innerHTML += "Yaşınız 2 yaştan küçük. Cetirizine için uzmanınıza danışınız.";
            recommendation = "Yaşınız 2 yaştan küçük. Cetirizine için uzmanınıza danışınız.";
        } else {  
            if (age >= 2 && age < 6) { doseAmount = 2.5 + (age - 2) * 0.625; LastMedicine = "ZYRTEC® 10 mg/mL oral damla, çözelti"; }
            else if (age >= 6 && age < 12) { doseAmount = 5 + (age - 6) * 0.833; LastMedicine = "ZYRTEC® 1 mg/mL şurup"; }
            else { doseAmount = 10; LastMedicine = "ZYRTEC® 10 mg film kaplı tablet"; }
            
            let supplement = getDoseSupplement(LastMedicine, doseAmount);
            doseResult.innerHTML += "Günlük Cetirizine Dozu: " + formatNumber(doseAmount) + " mg" + supplement + ", Günde 1 kez alınmalıdır.";
        }
        
        LastDose = formatNumber(doseAmount) + " mg" + getDoseSupplement(LastMedicine, doseAmount);
        
    }
    
    if (age < 3 || age >= 75) urgency = 4;
    else if (age < 6 || age >= 65) urgency = Math.max(urgency, 3);

    let result = "";
    let conclusion_rationale = "";
    let isRejected = false;

    document.getElementById('doseForm').style.display = 'none';
    document.getElementById('optionsContainer').style.display = 'none';
    document.getElementById('question').style.display = 'none';

    setTimeout(function() {
        if (medicineName && LastMedicine && doseAmount !== "NotApproved" && doseAmount !== "NotRecommended") {
            
            let infoLower = String(information || "").replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase();
            let medLower = String(medicineName || "").replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase();
            let infoRejected = false;
            
            if (infoLower.indexOf(medLower) !== -1) infoRejected = true;
            else if (medLower.indexOf("ibuprofen") !== -1 && infoLower.indexOf("ibuprofen") !== -1) infoRejected = true;
            else if (medLower.indexOf("paracetamol") !== -1 && infoLower.indexOf("paracetamol") !== -1) infoRejected = true;
            else if (medLower.indexOf("gaviscon") !== -1 && infoLower.indexOf("gaviscon") !== -1) infoRejected = true;
            else if (medLower.indexOf("cetirizine") !== -1 && infoLower.indexOf("cetirizine") !== -1) infoRejected = true;
            
            let isPreviouslyRejected = false;
            if (typeof rejectedMedicines !== 'undefined' && Array.isArray(rejectedMedicines)) {
                for (let i = 0; i < rejectedMedicines.length; i++) {
                    let rejLower = String(rejectedMedicines[i]).replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase();
                    if (medLower.indexOf(rejLower) !== -1 || rejLower.indexOf(medLower) !== -1) {
                        isPreviouslyRejected = true;
                        break;
                    }
                }
            }
            
            if (isPreviouslyRejected || infoRejected) {
                result = "-";
                conclusion_rationale = "Hastanın Özel Rahatsızlık Bilgisi'ne göre bu ilacı kullanması uygun değildir / sakıncalıdır.";
                isRejected = true;
            } else {
                let userConfirm = confirm("Önerilen İlaç: " + LastMedicine + "\n\nBu ilacı onaylıyor musunuz?\n(Eğer bu ilaç sizde zaten yeterli miktarda varsa 'İptal' butonuna basarak reddedebilirsiniz.)");
                if (!userConfirm) {
                    result = "-";
                    conclusion_rationale = "Bu ilaç hastada halen kullanıma yeterli olacak kadar bulunuyor.";
                    isRejected = true;
                } else {
                    result = "";
                    conclusion_rationale = "";
                }
            }
        } else {
            result = "-";
            if (doseAmount === "NotApproved") {
                conclusion_rationale = "Hastanın fiziksel profiline göre bu ilacı kullanması uygun görülmemiştir.";
            } else {
                conclusion_rationale = "Web Doktor'un önerisine göre hasta bu ilacı doktor kontrolünde tüketmesi gerekiyor.";
            }
            isRejected = true;
        }

        function sendDataToFile(fileName, extraParams = "") {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", fileName, true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = JSON.parse(xhr.responseText.replace(/^\uFEFF/, '').trim());
                    if (response.status == "error") {
                        console.error(fileName + " error: " + response.message);
                    } else {
                        console.log(fileName + " successful: " + response.message);
                        if (fileName === "save_dose.php" && response.last_id) {
                            if (isRejected) {
                                let combinedText = conclusion_rationale ? "(" + conclusion_rationale + ") " : "";
                                let msg = combinedText !== ""
                                    ? "İlacınız, bu " + combinedText + "gerekçelerden dolayı reddedilmiştir. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz..."
                                    : "İlacınız reddedilmiştir. {time} Saniye içinde Özgeçmiş sayfanıza yönlendiriliyorsunuz...";
                                startCountdown(5, msg, "/resume");
                            } else {
                                startStatusPolling(response.last_id);
                            }
                        }
                    }
                }
            };
            var data = "user_id=" + user_id + "&medicine=" + encodeURIComponent(medicineName) + "&brand=" + encodeURIComponent(LastMedicine) + "&name=" + encodeURIComponent(name) + "&surname=" + encodeURIComponent(surname) + "&information=" + encodeURIComponent(information) + "&age=" + encodeURIComponent(customAge) + "&weight=" + weight + "&dose=" + encodeURIComponent(LastDose) + "&dailyAmount=" + encodeURIComponent(formatNumber(doseFrequency)) + "&discomfort=" + encodeURIComponent(discomfort) + "&subdiscomfort=" + encodeURIComponent(subDiscomfort) + "&recommendation=" + encodeURIComponent(recommendation) + "&guidance=" + encodeURIComponent(guidance) + "&urgency=" + encodeURIComponent(urgency) + "&pharmacy=" + encodeURIComponent(pharmacy) + "&types=" + encodeURIComponent("eczane") + "&conclusion_rationale=" + encodeURIComponent(conclusion_rationale) + "&result=" + encodeURIComponent(result) + "&csrf_token=" + encodeURIComponent(csrf_token);
            if (extraParams) { data += "&" + extraParams; }
            xhr.send(data);
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

        if (!isRejected) {
            messageDiv.textContent = "Merhaba, " + name + " " + surname + " ilacınız onaylandığında yönlendirileceksiniz.";
        } else {
            messageDiv.textContent = "İşlem kaydediliyor...";
        }

        sendDataToFile("save_dose.php");
    }, 300);
}

function checkPharmacy() {
    const input = document.getElementById("pharmacyInput").value.trim();

    if (!input) {
        document.getElementById("pharmacyError").innerText = "Onay kodunu giriniz.";
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "check_pharmacy.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            let res = JSON.parse(xhr.responseText.replace(/^\uFEFF/, '').trim());

            if (res.status === "ok") {
                pharmacy = res.pharmacy;
                document.getElementById("pharmacyGate").style.display = "none";
                document.getElementById("mainContainer").classList.remove("hidden");
                setTimeout(typeQuestion, 1250);
            } else {
                document.getElementById("pharmacyError").innerText = "Onay kodu hatalı.";
            }
        }
    };

    xhr.send("code=" + encodeURIComponent(input) + "&csrf_token=" + encodeURIComponent(csrf_token));
}

document.addEventListener("keypress", function(event) {
    if (event.key === "Enter") {
        if (event.target && event.target.id === "pharmacyInput") {
            event.preventDefault();
            checkPharmacy();
        } else if (event.target && event.target.id === "weight") {
            event.preventDefault();
            calculateDose();
        }
    }
});