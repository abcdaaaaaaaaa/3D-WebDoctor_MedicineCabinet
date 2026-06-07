let pendingPage = 1;
let pastPage = 1;
const itemsPerPage = 3;

let currentActiveId = null;
let conclusion_rationale = "";
let detail_conclusion_rationale = "";
let sendValue = 0; 

function getUrgencyBadge(urgency) {
    let classes = "";
    if (urgency === "Düşük") {
        classes = "bg-emerald-50 text-emerald-700 border-emerald-200";
    } else if (urgency === "Orta") {
        classes = "bg-amber-50 text-amber-700 border-amber-200";
    } else if (urgency === "Yüksek" || urgency === "Acil") {
        classes = "bg-rose-50 text-rose-700 border-rose-200";
    } else {
        classes = "bg-slate-50 text-slate-700 border-slate-200";
    }
    return `<span class="px-2.5 py-1 rounded-md text-xs font-medium border ${classes} whitespace-nowrap">${urgency}</span>`;
}

function renderPendingTable() {
    const tbody = document.getElementById('pending-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';
    const start = (pendingPage - 1) * itemsPerPage;
    const pageItems = pendingData.slice(start, start + itemsPerPage);

    pageItems.forEach(item => {
        const row = `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="py-4 px-6 font-medium text-slate-900 whitespace-nowrap">${item.date}</td>
                <td class="py-4 px-6">
                    <div class="font-semibold text-slate-900 whitespace-nowrap">${item.name}</div>
                    <div class="text-xs text-slate-500 mt-0.5 whitespace-nowrap">Yaş: ${item.age} Kg: ${item.weight}<br>USER-ID: ${item.userId} | ILL-ID: ${item.patientId}</div>
                </td>
                <td class="py-4 px-6">
                    <div class="text-xs text-indigo-700 font-bold whitespace-nowrap">${item.mainDisease} <i class="fa-solid fa-angle-right text-[10px] mx-1 text-slate-400"></i></div>
                    <div class="text-xs text-indigo-700 font-bold whitespace-nowrap">${item.subDisease}</div>
                </td>
                <td class="py-4 px-6">
                    <div class="font-medium text-indigo-900">${item.drugName} <span class="text-xs font-normal text-slate-500">(${item.drugBrand})</span></div>
                </td>
                <td class="py-4 px-6">
                    <div class="text-xs text-slate-900 mt-0.5 whitespace-nowrap">Günde ${item.dailyAmount} kez</div>
                    <div class="text-xs text-slate-900 mt-0.5 whitespace-nowrap">${item.dose}</div>
                </td>
                <td class="py-4 px-6">${getUrgencyBadge(item.urgency)}</td>
                <td class="py-4 px-6 text-center text-lg">${item.type}</td>
                <td class="py-4 px-6 text-center">
                    <button onclick="openDetailModal(${item.id})" class="bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 transition-colors border border-slate-200 whitespace-nowrap">
                        <i class="fa-solid fa-eye"></i> Detayları Gör
                    </button>
                </td>
            </tr>`;
        tbody.innerHTML += row;
    });

    const totalPages = Math.ceil(pendingData.length / itemsPerPage) || 1;
    document.getElementById('page-info-pending').innerText = `${pendingPage} / ${totalPages}`;
    document.getElementById('btn-pending-prev').disabled = pendingPage === 1;
    document.getElementById('btn-pending-next').disabled = pendingPage === totalPages;
}

function renderPastTable() {
    const tbody = document.getElementById('past-table-body');
    if (!tbody) return;
    tbody.innerHTML = '';
    const start = (pastPage - 1) * itemsPerPage;
    const pageItems = pastData.slice(start, start + itemsPerPage);

    pageItems.forEach(item => {
        const isApproved = item.status === '+';
        const statusClass = isApproved ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200';
        const statusText = isApproved ? 'Onaylandı (+)' : 'Reddedildi (-)';
        const specialInfoText = item.specialInfo ? item.specialInfo : "Herhangi bir özel rahatsızlığı olmadığı bildirilmiştir.";

        const row = `
            <tr class="hover:bg-slate-50/80 transition-colors">
                <td class="py-4 px-6 font-medium text-slate-900 whitespace-nowrap">${item.date}</td>
                <td class="py-4 px-6 align-top">
                    <div class="font-semibold text-slate-900 whitespace-nowrap">${item.name}</div>
                    <div class="text-xs text-slate-500 mt-0.5 mb-1.5 whitespace-nowrap">Yaş: ${item.age} Kg: ${item.weight}<br>USER-ID: ${item.userId} | ILL-ID: ${item.patientId}</div>
                    <div class="text-xs border-t border-slate-200 pt-1.5">
                        <span class="font-semibold text-slate-700">Özel Rahatsızlık Bilgisi:</span>
                        <div class="text-slate-500 mt-0.5">${specialInfoText}</div>
                    </div>
                </td>
                <td class="py-4 px-6 align-top">
                    <div class="text-xs text-indigo-700 font-bold whitespace-nowrap">${item.mainDisease} <i class="fa-solid fa-angle-right text-[10px] mx-1 text-slate-400"></i></div>
                    <div class="text-xs text-indigo-700 font-bold whitespace-nowrap">${item.subDisease}</div>
                </td>
                <td class="py-4 px-6 align-top">
                    <div class="font-medium text-indigo-900">${item.drugName} <span class="text-xs font-normal text-slate-500">(${item.drugBrand})</span></div>
                </td>
                <td class="py-4 px-6 align-top">
                    <div class="text-xs text-slate-900 mt-0.5 whitespace-nowrap">Günde ${item.dailyAmount} kez</div>
                    <div class="text-xs text-slate-900 mt-0.5 whitespace-nowrap">${item.dose}</div>
                </td>
                <td class="py-4 px-6 align-top">${getUrgencyBadge(item.urgency)}</td>
                <td class="py-4 px-6 text-center text-lg align-top">${item.type}</td>
                <td class="py-4 px-6 align-top">
                    <div class="text-xs text-slate-600">${item.rationale}</div>
                </td>
                <td class="py-4 px-6 align-top">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold ${statusClass} border whitespace-nowrap">${statusText}</span>
                </td>
            </tr>`;
        tbody.innerHTML += row;
    });

    const totalPages = Math.ceil(pastData.length / itemsPerPage) || 1;
    document.getElementById('page-info-past').innerText = `${pastPage} / ${totalPages}`;
    document.getElementById('btn-past-prev').disabled = pastPage === 1;
    document.getElementById('btn-past-next').disabled = pastPage === totalPages;
}

function changePage(type, direction) {
    if (type === 'pending') {
        pendingPage += direction;
        renderPendingTable();
    } else {
        pastPage += direction;
        renderPastTable();
    }
}

function confirmAction(message) {
    return confirm(message || "İşlemi onaylıyor musunuz?");
}

function toggleVisibility(elementId, show = true) {
    const element = document.getElementById(elementId);
    if(element) element.classList.toggle('hidden', !show);
}

function resetWizard() {
    toggleVisibility('resultContainer', true);
    toggleVisibility('approvalReasonContainer', false);
    toggleVisibility('detailedRejectionQuestionContainer', false);
    toggleVisibility('detailedRejectionReasonContainer', false);
    toggleVisibility('rejectionReasonContainer', false);
    toggleVisibility('rejectionReasonContainerFilled', false);
    
    document.getElementById('approvalText').value = "Sonuçlar Web Doktor tarafından uygun görüldü.";
    document.getElementById('detailedRejectionText').value = "";
    document.querySelectorAll('input[name="rejectionReason"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="rejectionReasonFilled"]').forEach(cb => { if(!cb.disabled) cb.checked = false; });
    
    conclusion_rationale = "";
    detail_conclusion_rationale = "";
}

function openDetailModal(id) {
    const item = pendingData.find(d => String(d.id) === String(id));
    if (!item) return;

    currentActiveId = id;
    document.getElementById('modal-patient-name').innerText = item.name;
    document.getElementById('modal-drug').innerText = item.drugName + " (" + item.drugBrand + ")";
    document.getElementById('modal-age').innerText = item.age;
    document.getElementById('modal-weight').innerText = item.weight;
    document.getElementById('modal-dose').innerText = item.dose;
    document.getElementById('modal-daily-amount').innerText = item.dailyAmount;
    document.getElementById('modal-disease').innerText = item.mainDisease + " - " + item.subDisease;
    document.getElementById('modal-special-info').value = item.specialInfo;
    document.getElementById('modal-recommendation').innerText = item.recommendation || 'Herhangi bir öneri verilmemiştir.';
    document.getElementById('modal-guidance').innerText = item.guidance;

    const riskToggle = document.getElementById('modal-risk-toggle');
    if (riskToggle) {
        riskToggle.checked = (parseInt(item.userId) < 0);
    }

    resetWizard();

    const modal = document.getElementById('detail-modal');
    modal.classList.remove('hidden');
    setTimeout(() => { modal.classList.remove('opacity-0'); modal.querySelector('div').classList.remove('scale-95'); }, 10);
}

function closeModal() {
    const modal = document.getElementById('detail-modal');
    modal.classList.add('opacity-0');
    modal.querySelector('div').classList.add('scale-95');
    setTimeout(() => { modal.classList.add('hidden'); currentActiveId = null; }, 300);
}

function sendYes() {
    if (confirmAction()) {
        toggleVisibility('resultContainer', false);
        toggleVisibility('approvalReasonContainer', true);
    }
}

function sendNo() {
    if (confirmAction()) {
        toggleVisibility('resultContainer', false);
        toggleVisibility('detailedRejectionQuestionContainer', true);
    }
}

function detailedRejectionYes() {
    if (confirmAction()) {
        toggleVisibility('detailedRejectionQuestionContainer', false);
        toggleVisibility('detailedRejectionReasonContainer', true);
        sendValue = 0;
    }
}

function detailedRejectionNo() {
    if (confirmAction()) {
        toggleVisibility('detailedRejectionQuestionContainer', false);
        toggleVisibility('rejectionReasonContainer', true);
        sendValue = 1;
    }
}

function sendDetailedRejectionReason() {
    const detailedText = document.getElementById('detailedRejectionText').value.trim();
    if (detailedText.length > 500) {
        alert("Detaylı Red gerekçesi 500 karakterden uzun olamaz.");
        return;
    }
    if (confirmAction() && detailedText !== "") {
        detail_conclusion_rationale = detailedText;
        alert("Detaylı Red gerekçesi kaydedildi.");
        toggleVisibility('detailedRejectionReasonContainer', false);
        toggleVisibility('rejectionReasonContainerFilled', true);
    } else if (detailedText === "") {
        alert("Lütfen ilgili gerekçeyi doldurunuz.");
    }
}

function sendRejectionReason() {
    if (confirmAction()) {
        const checkedOptions = document.querySelectorAll('input[name="rejectionReason"]:checked');
        if (checkedOptions.length > 0) {
            const selectedReasons = Array.from(checkedOptions).map(option => option.value);
            conclusion_rationale = selectedReasons.join(", ");
            alert("Red gerekçesi toplandı. Veritabanına gönderiliyor...");
            
            if (sendValue == 1) { 
                executeDatabaseUpdate('-', conclusion_rationale, ''); 
            }
        } else {
            alert("Lütfen en az bir red gerekçesi seçin.");
        }
    }
}

function sendRejectionReasonFilled() {
    if (confirmAction()) {
        const selectedReasonsFilled = Array.from(document.querySelectorAll('input[name="rejectionReasonFilled"]:checked'))
            .map(option => option.value);
        conclusion_rationale = selectedReasonsFilled.join(", ");
        alert("Detaylı ve seçmeli red verileri toplandı. Veritabanına gönderiliyor...");
        
        executeDatabaseUpdate('-', conclusion_rationale, detail_conclusion_rationale);
    }
}

function sendApprovalReason() {
    const approvalText = document.getElementById('approvalText').value.trim();
    if (approvalText.length > 500) {
        alert("Kabul gerekçesi 500 karakterden uzun olamaz.");
        return;
    }
    if (confirmAction()) {
        detail_conclusion_rationale = approvalText;
        alert("Kabul gerekçesi toplandı. Veritabanına gönderiliyor...");
        
        executeDatabaseUpdate('+', '', detail_conclusion_rationale);
    }
}

function updateInformation() {
    if (!currentActiveId) return;
    const infoText = document.getElementById('modal-special-info').value.trim();
    const riskToggle = document.getElementById('modal-risk-toggle');
    const isRisky = riskToggle ? riskToggle.checked : false;

    if (infoText.length > 500) {
        alert("Özel rahatsızlık bilgisi 500 karakterden uzun olamaz.");
        return;
    }

    if (isRisky && !infoText) {
        alert('Özel rahatsızlık bilgisi girilmeden hastanın "Riskli" durumuna getirilmesine izin verilmez.');
        if (riskToggle) riskToggle.checked = false;
        return;
    }

    if (!confirmAction("Özel rahatsızlık bilgisini veya risk durumunu güncellemek istediğinize emin misiniz?")) return;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "approval", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.status === "success") {
                    alert("Başarıyla güncellenmiştir.");
                    closeModal();
                    fetchUpdates();
                } else {
                    alert(response.message);
                }
            } catch (e) {
                alert("Sunucudan geçersiz bir yanıt döndü.");
            }
        }
    };
    const params = "action=update_information" +
                   "&id=" + encodeURIComponent(currentActiveId) +
                   "&information=" + encodeURIComponent(infoText) +
                   "&is_risky=" + isRisky +
                   "&csrf_token=" + encodeURIComponent(csrf_token);
    xhr.send(params);
}

function executeDatabaseUpdate(resultSign, rationale, detailRationale) {
    if (!currentActiveId) return;

    const xhr = new XMLHttpRequest();
    xhr.open("POST", "approval", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.status === "success") {
                    closeModal();
                    fetchUpdates();
                } else {
                    alert(response.message);
                }
            } catch (e) {
                alert("Sunucudan geçersiz bir yanıt döndü.");
            }
        }
    };
    
    const params = "action=update_status" +
                   "&id=" + encodeURIComponent(currentActiveId) +
                   "&result=" + encodeURIComponent(resultSign) +
                   "&conclusion_rationale=" + encodeURIComponent(rationale) +
                   "&detail_conclusion_rationale=" + encodeURIComponent(detailRationale) +
                   "&csrf_token=" + encodeURIComponent(csrf_token);
    xhr.send(params);
}

function fetchUpdates() {
    if (currentActiveId !== null) return; 

    const xhr = new XMLHttpRequest();
    xhr.open("GET", "approval?action=fetch_data&csrf_token=" + encodeURIComponent(csrf_token), true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.pending && response.past) {
                    pendingData = response.pending;
                    pastData = response.past;
                    renderPendingTable();
                    renderPastTable();
                }
            } catch (e) {
                console.error("Veriler yenilenirken arka planda bir hata oluştu.");
            }
        }
    };
    xhr.send();
}

document.addEventListener('DOMContentLoaded', () => {
    renderPendingTable();
    renderPastTable();
    
    const riskToggle = document.getElementById('modal-risk-toggle');
    const specialInfoInput = document.getElementById('modal-special-info');
    
    if (riskToggle && specialInfoInput) {
        riskToggle.addEventListener('change', function() {
            if (this.checked && !specialInfoInput.value.trim()) {
                alert('Özel rahatsızlık bilgisi girilmeden hastanın "Riskli" durumuna getirilmesine izin verilmez.');
                this.checked = false;
            }
        });
        
        specialInfoInput.addEventListener('input', function() {
            if (this.value.length > 500) {
                this.value = this.value.substring(0, 500);
            }
            if (!this.value.trim()) {
                riskToggle.checked = false;
            }
        });
    }
    
    setInterval(fetchUpdates, 15000); 
});