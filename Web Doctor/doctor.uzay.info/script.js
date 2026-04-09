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


let buffer = "";
let hasStartedSpeaking = false;

function typeQuestion() {
    var doctorImage = document.getElementById("doctorImage");
    if (index === 0) { 
        buffer = "";
        hasStartedSpeaking = false;
        doctorImage.src = "../images/doctor.gif";
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
                doctorImage.src = "../images/doctor.png";
            };
            window.speechSynthesis.speak(utter);
            hasStartedSpeaking = true;
        }

        setTimeout(typeQuestion, typingSpeed);
    }
}


let discomfort, subDiscomfort, guidance, medicineName, StomachIntensity, temp, res, PainIntensity, doseAmount, doseFrequency;
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
            if (subOption === 1 || subOption === 4) {
                guidance = window.sentences_1;
				StomachIntensity = 1;
            } else if (subOption === 2 || subOption === 3) {
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

let sumbit = 0;

function nexttemp() { 
    askNextQuestion(6);
    updateOptionsForTemp();     
}

function submittemp() {
    const inputElement = document.getElementById('tempInput');
    temp = inputElement.value;
    if (temp >= 36.1 && temp <= 47) {
        document.getElementById('inputContainer').style.display = 'none';
        document.getElementById('inputContainerWrapper').style.display = 'none';
        nexttemp();
    } else if (temp) alert('Lütfen geçerli bir ateş derecesi giriniz.');
    else alert('Lütfen bir ateş derecesi giriniz.');
}

function updateOptions(optionNumber) {
    var newOptions = [];
    switch (optionNumber) {
        case 1:
            newOptions = ["Baş Ağrısı", "Diş Ağrısı", "Karın Ağrısı", "Kas Ağrısı"];
            break;
        case 2:
            newOptions = ["Hazımsızlık", "Kusma", "İshal", "Mide Ağrısı", "Hafif Bir Rahatsızlık"];
            break;
        case 3:
            document.getElementById('inputContainerWrapper').style.display = 'flex';
            document.getElementById('tempInput').addEventListener('keypress', function(event) { if (event.key === 'Enter') { submittemp(); } });
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
    } else { temp = 37.2
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
    } else if (subOption === 1 || subOption === 3) {
        guidance = window.sentences_10;
    } else if (subOption === 2) {
        guidance = window.sentences_11;
    } else if (subOption === 5) {
        if (temp <= 37.2) {
		  guidance = window.sentences_12;
          urgent = -2;
		} else if (temp < 37.5) {
		  guidance = window.sentences_12;
          urgent = -1;
		} else guidance = window.sentences_10;
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

function calculateDose() {
    var weight = parseFloat(document.getElementById("weight").value).toFixed(2);
    doseResult.innerHTML += "Mevcut Hasta Bilgisi: " + customAge + " " + weight + " kilo.<br><br>";

	if (mainOptionSelected === 1) {
        medicineName = "Ibuprofen mg";
        if (res2 == 3) {
           mainOptionSelected = 3;
           doseResult.innerHTML = "";
           if (PainIntensity === 1 || PainIntensity === 2) {  urgent = -2; }
           if (PainIntensity === 3) { urgent = -1; }
           if (PainIntensity === 4) { urgent = 0; }
             calculateDose();
        } else if (age < 0.25) {
             doseAmount = "NotApproved";
             doseFrequency = "NotApproved";
             doseResult.innerHTML += "Yaşınız 3 aydan küçük. Ibuprofen için uzmanınıza danışın.";
        } else if (age < 18) {
            doseAmount = weight * 6;
            if (doseAmount > 400) doseAmount = 400;     
            if (PainIntensity === 1 || PainIntensity === 2) {doseFrequency = 3;}
            else if (PainIntensity === 3 || PainIntensity === 4) {doseFrequency = 4;}
            doseResult.innerHTML += "Günlük Ibuprofen Dozu: " + formatNumber(doseAmount) + " mg, Günde " + doseFrequency + " kez alınmalıdır.";
        } else {
            if (PainIntensity === 1) {doseFrequency = 4; doseAmount = 200;}
            else if (PainIntensity === 2) {doseFrequency = 6; doseAmount = 200;}
            else if (PainIntensity === 3) {doseFrequency = 4; doseAmount = 400;}
            else if (PainIntensity === 4) {doseFrequency = 6; doseAmount = 400;}
            doseResult.innerHTML += "Günlük Ibuprofen Dozu: " + formatNumber(doseAmount) + " mg, Günde " + doseFrequency + " kez alınmalıdır.";
        }
    } else if (mainOptionSelected === 2) {
        if (age < 1) {
         medicineName = "Gaviscon Infant ml";
         doseAmount = "NotRecommended";
         doseFrequency = "NotRecommended";
         doseResult.innerHTML += "Yaşınız 1 yaştan küçük. Gaviscon ve Gaviscon Infant'ı kullanmanız önerilmez. Lütfen ilaç için uzmanınıza danışınız.";
        } else if (age < 3) {
         medicineName = "Gaviscon Infant ml";
         doseFrequency = "max6";
         doseResult.innerHTML += "Yaşınız 3 yaştan küçük. Bu ilacı doktor gözetiminde alın. Gaviscon Infant'ı kullanmanız önerilir.<br><br>";
         doseResult.innerHTML += "Biberonla besleme:<br>";
         doseResult.innerHTML += "Her poşeti 115 ml biberonla karıştırın. ";
         doseResult.innerHTML += "İyice çalkalayın ve bebeği normal şekilde besleyin. ";
         doseResult.innerHTML += "Beslenme bittikten sonra Gaviscon Infant'ı bir kaşık veya şişe kullanarak uygulayın.<br><br>";
         doseResult.innerHTML += "Emzirme:<br>";
         doseResult.innerHTML += "Her poşeti bir çay kaşığı kaynamış ancak soğutulmuş suyla karıştırın. ";
         doseResult.innerHTML += "Pürüzsüz bir macun oluşacaktır. 2 çay kaşığı kaynamış, sarmal su ekleyin ve karıştırın. "; 
         doseResult.innerHTML += "Mamanın yarısında, uygulamak için bir kaşık veya biberon kullanın.<br><br>";
         if (weight < 4.5){
           doseAmount = 115;
           doseResult.innerHTML += "Günlük Gaviscon Infant Poşet Dozu: 1 Poşet, Günde 6 kereden fazla almayın.";
         } else {
           doseAmount = 230;
           doseResult.innerHTML += "Günlük Gaviscon Infant Poşet Dozu: 2 Poşet, Günde 6 kereden fazla almayın.";
         }
        } else if (age < 6) {
          medicineName = "Gaviscon ml";
          doseFrequency = 4;
          doseAmount = 5 * (age - 2) / 8 + 2.5;	
          if (StomachIntensity === 0) doseAmount = 2.5;
          doseResult.innerHTML += "Yaşınız 6 yaştan küçük. Bu ilaci doktor gözetiminde alın.<br>";
          doseResult.innerHTML += "Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " ml, Günde 4 kez alınmalıdır.";		 
        } else if (age <= 18) { // <= exceptional
          medicineName = "Gaviscon ml";
          doseFrequency = 4;
          doseAmount = 5 * age / 6;
          if (StomachIntensity === 0 && age < 12) doseAmount = 5;
          else if (StomachIntensity === 0) doseAmount = 10;
          doseResult.innerHTML += "Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " ml, Günde 4 kez alınmalıdır.";
        } else {
          medicineName = "Gaviscon ml";
          doseFrequency = 4;
          if (StomachIntensity === 0) doseAmount = 10;
          else if (StomachIntensity === 1) doseAmount = 15;
          else if (StomachIntensity === 2) doseAmount = 20;
          doseResult.innerHTML += "Günlük Gaviscon Dozu: " + formatNumber(doseAmount) + " ml, Günde 4 kez alınmalıdır.";
        }
	} else if (mainOptionSelected === 3) {
        medicineName = "Paracetamol mg";
        if (urgent == 1) medicineName = "Paracetamol mg EmergencyRoom";
        
        if (age < 0.0833) {
            doseAmount = "NotApproved";
            doseFrequency = "NotApproved";
            doseResult.innerHTML += "Yaşınız 1 aydan küçük. Paracetamol için uzmanınıza danışın."; 
        } else if (age < 18) {
            if (weight < 5) {
                doseAmount = "NotApproved";
                doseFrequency = "NotApproved";
                doseResult.innerHTML += "Kilonuz çok düşük. Paracetamol için uzmanınıza danışın.";
            } else if (urgent == -2) { doseAmount = weight * 10; doseFrequency = 4; }
            else { doseAmount = weight * 15; doseFrequency = 4; }
        } else if (weight < 40) { doseAmount = 500; doseFrequency = 2; }
        else doseAmount = tempdose(temp, weight);
        
        if (age >= 0.0833 && weight >= 5) {
            doseAmount = Math.min(doseAmount, 1000);
            if (urgent < 0) doseAmount = Math.min(doseAmount, 500);
            urgent == 1 ? doseResult.innerHTML += "Acil Alınacak Paracetamol Dozu: " + formatNumber(doseAmount) + " mg." 
            : doseResult.innerHTML += "Günlük Paracetamol Dozu: " + formatNumber(doseAmount) + " mg, Günde " + doseFrequency + " kez alınmalıdır.";
        }

    } else if (mainOptionSelected === 4) {
        medicineName = "Cetirizine mg";
        doseFrequency = 1;
        
        if (age < 2) {
            doseAmount = "NotRecommended";
            doseFrequency = "NotRecommended";
            doseResult.innerHTML += "Yaşınız 2 yaştan küçük. Cetirizine için uzmanınıza danışınız.";
        } else {  
            if (age >= 2 && age < 6) { doseAmount = 2.5 + (age - 2) * 0.625; }
            else if (age >= 6 && age < 12) { doseAmount = 5 + (age - 6) * 0.833; }
            else { doseAmount = 10; }
            doseResult.innerHTML += "Günlük Cetirizine Dozu: " + formatNumber(doseAmount) + " mg, Günde 1 kez alınmalıdır.";
        }
}


    function sendDataToFile(fileName, extraParams = "") {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", fileName, true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.status == "error") {
                    console.error(fileName + " error: " + response.message);
                } else {
                    console.log(fileName + " successful: " + response.message);
                    if (fileName === "save_dose.php" && response.last_id) {
                        sendDataToFile("save_medicine.php", "id=" + encodeURIComponent(response.last_id));
                    }
                }
            }
        };
        var data = "user_id=" + user_id + "&medicine=" + encodeURIComponent(medicineName) + "&name=" + encodeURIComponent(name) + "&surname=" + encodeURIComponent(surname) + "&information=" + encodeURIComponent(information) + "&age=" + encodeURIComponent(customAge) + "&weight=" + weight + "&dose=" + encodeURIComponent(formatNumber(doseAmount)) + "&dailyAmount=" + encodeURIComponent(formatNumber(doseFrequency)) + "&discomfort=" + encodeURIComponent(discomfort) + "&subdiscomfort=" + encodeURIComponent(subDiscomfort) + "&guidance=" + encodeURIComponent(guidance);
        if (extraParams) { data += "&" + extraParams; }
        xhr.send(data);
    }
    
    sendDataToFile("save_dose.php");
    
    document.getElementById('doseForm').style.display = 'none';
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
    let secondsLeft = 7;
    messageDiv.textContent = `${secondsLeft} saniye içinde yönlendiriliyorsunuz...`;
    
    const interval = setInterval(() => {
      secondsLeft--;
      if (secondsLeft > 0) { messageDiv.textContent = `${secondsLeft} saniye içinde yönlendiriliyorsunuz...`; } 
      else { clearInterval(interval); window.location.href = 'save_medicine'; }
    }, 1000);
}

setTimeout(typeQuestion, 1250);
