// Words
window.w1  = [ "alınız.", "kullanınız.", "almalısınız.", "kullanmalısınız." ]; // 4
window.w2  = [ "Belirtileriniz", "Rahatsızlıklarınız", "Şikayetleriniz", "Hastalıklarınız", "Sorunlarınız" ]; // 5
window.w3  = [ "uzmana başvurunuz.", "uzmana danışınız.", "uzmana görününüz.", "uzman kontrolüne gidiniz." ]; // 4
window.w4  = [ "sürerse", "sürmesi durumunda" ]; // 2
window.w5  = [ "uzun", "fazla" ]; // 2
window.w6  = [ "Hemen", "Derhal", "Hiç vakit kaybetmeden" ]; // 3
window.w7  = [ "acile başvurunuz.", "acile gidiniz.", "acile görününüz.", "acilden destek alınız.", "acile yöneliniz." ]; // 5
window.w8  = [ "almanız", "kullanmanız" ]; // 2
window.w9  = [ "önerilir.", "tavsiye edilir." ]; // 2
window.w10 = [ "Belirtilerinizin", "Rahatsızlıklarınızın", "Şikayetlerinizin", "Hastalıklarınızın", "Sorunlarınızın" ]; // 5
window.w11 = [ "başvurunuz.", "gidiniz.", "görününüz.", "yöneliniz.", "başvurmalısınız.", "gitmelisiniz.", "görünmelisiniz.", "yönelmelisiniz." ]; // 8
window.w12 = [ "başlatınız.", "izleyiniz." ]; // 2
window.w13  = [ "hemen", "derhal", "hiç vakit kaybetmeden" ]; // 3
window.w14  = [ "veya", "ya da" ]; // 2

// Word Weights
const weights = {
  w1: [4,3,2,1],
  w2: [5,4,3,2,1],
  w3: [4,3,2,1],
  w4: [2,1],
  w5: [2,1],
  w6: [3,2,1],
  w7: [5,4,3,2,1],
  w8: [2,1],
  w9: [2,1],
  w10:[5,4,3,2,1],
  w11:[8,7,6,5,4,3,2,1],
  w12:[2,1],
  w13:[3,2,1],
  w14:[2,1]
};

// Weights Function
function getWeightedRandomIndex(weightsArr) {
  let total = 0;
  for (let w of weightsArr) total += w;
  let rand = Math.random() * total;
  let sum = 0;
  for (let i = 0; i < weightsArr.length; i++) {
    sum += weightsArr[i];
    if (rand < sum) return i;
  }
  return weightsArr.length - 1;
}

// Words Function
function selectWord(wKey) {
  const arr = window[wKey];
  const wArr = weights[wKey];
  const idx = getWeightedRandomIndex(wArr);
  return arr[idx];
}

// 320 Sentences
function sentences1() {
  return "Gaviscon " + selectWord("w1") + " " + selectWord("w2") + " 5 günden daha " + selectWord("w5") + " " + selectWord("w4") + " " + selectWord("w3");
}

// 80 Sentences
function sentences2() {
  return "Gaviscon " + selectWord("w1") + " " + selectWord("w10") + " hemen ardından " + selectWord("w3");
}

// 60 Sentences
function sentences3() {
  return "Cetirizine " + selectWord("w1") + " " + selectWord("w6") + " " + selectWord("w7");
}

// 320 Sentences
function sentences4() {
  return "Cetirizine " + selectWord("w8") + " " + selectWord("w9") + " " + selectWord("w2") + " 5 günden daha " + selectWord("w5") + " " + selectWord("w4") + " " + selectWord("w3");
}

// 80 Sentences
function sentences5() {
  return "Ibuprofen " + selectWord("w1") + " " + selectWord("w10") + " hemen ardından " + selectWord("w3");
}

// 64 Sentences
function sentences6() {
  return "Ibuprofen " + selectWord("w8") + " " + selectWord("w9") + " Ağrınız 5 günden daha " + selectWord("w5") + " " + selectWord("w4") + " " + selectWord("w3");
}

// 80 Sentences
function sentences7() {
  return "Paracetamol " + selectWord("w1") + " " + selectWord("w10") + " hemen ardından " + selectWord("w3");
}

// 64 Sentences
function sentences8() {
  return "Paracetamol " + selectWord("w1") + " Ağrınız 2 günden daha " + selectWord("w5") + " " + selectWord("w4") + " " + selectWord("w3");
}

// 24 Sentences
function sentences9() {
  return selectWord("w6") + " hastanenin acil servisine " + selectWord("w11");
}

// 768 Sentences
function sentences10() {
  return "Paracetamol " + selectWord("w1") + " Ateş takibi " + selectWord("w12") + " Ateşiniz ilaçla düşmezse " + selectWord("w14") +  " ilaçla düşüp 2 günden daha " + selectWord("w5") + " " + selectWord("w4") + " " + selectWord("w13") + " " + selectWord("w3");
}

// 80 Sentences
function sentences11() {
  return "Paracetamol " + selectWord("w1") + " " + selectWord("w10") + " hemen ardından " + selectWord("w3");
}

// 320 Sentences
function sentences12() {
  return "Paracetamol " + selectWord("w1") + " " + selectWord("w2") + " 5 günden daha " + selectWord("w5") + " " + selectWord("w4") + " " + selectWord("w3");
}

// 2260 Sentences
window.sentences_1 = sentences1();
window.sentences_2 = sentences2();
window.sentences_3 = sentences3();
window.sentences_4 = sentences4();
window.sentences_5 = sentences5();
window.sentences_6 = sentences6();
window.sentences_7 = sentences7();
window.sentences_8 = sentences8();
window.sentences_9 = sentences9();
window.sentences_10 = sentences10();
window.sentences_11 = sentences11();
window.sentences_12 = sentences12();