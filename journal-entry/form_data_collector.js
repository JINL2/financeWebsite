/**
 * Journal Entry - Form Data Collection
 * Details 모달 시스템과 연동된 폼 데이터 수집
 */

/**
 * 전체 폼 데이터 수집 (Details 포함)
 * @returns {object}
 */
function collectFormData() {
    const formData = {
        // 기본 필드
        p_company_id: getCompanyId(),
        p_created_by: getUserId(),
        p_entry_date: document.getElementById('entry_date')?.value || new Date().toISOString().slice(0, 10),
        p_description: document.getElementById('description')?.value || '',
        p_store_id: document.getElementById('store_id')?.value || null,
        p_counterparty_id: null,
        p_if_cash_location_id: null,
        p_lines: []
    };
    
    // 각 라인별 데이터 수집
    const journalLines = document.querySelectorAll('.journal-line');
    let totalDebit = 0;
    let totalCredit = 0;
    let firstCounterparty = null;
    let firstCashLocation = null;
    
    journalLines.forEach((lineElement, index) => {
        const lineData = collectLineData(lineElement);
        if (lineData && (lineData.debit > 0 || lineData.credit > 0)) {
            formData.p_lines.push(lineData);
            
            totalDebit += lineData.debit || 0;
            totalCredit += lineData.credit || 0;
            
            // 첫 번째 거래처 및 현금 위치 추출
            if (lineData.debt && lineData.debt.counterparty_id && !firstCounterparty) {
                firstCounterparty = lineData.debt.counterparty_id;
            }
            if (lineData.cash && lineData.cash.cash_location_id && !firstCashLocation) {
                firstCashLocation = lineData.cash.cash_location_id;
            }
        }
    });
    
    // 최상위 거래처 및 현금 위치 설정
    formData.p_counterparty_id = firstCounterparty;
    formData.p_if_cash_location_id = firstCashLocation;
    formData.p_base_amount = Math.max(totalDebit, totalCredit);
    
    return formData;
}

/**
 * 개별 라인 데이터 수집
 * @param {HTMLElement} lineElement 
 * @returns {object|null}
 */
function collectLineData(lineElement) {
    const lineIndex = lineElement.dataset.lineIndex;
    
    // 기본 라인 데이터
    const accountInput = lineElement.querySelector('.account-id-hidden');
    const descInput = lineElement.querySelector('input[name="line_description[]"]');
    const debitInput = lineElement.querySelector('.debit-input');
    const creditInput = lineElement.querySelector('.credit-input');
    
    if (!accountInput?.value) {
        return null; // 계정이 선택되지 않은 라인은 제외
    }
    
    const lineData = {
        account_id: accountInput.value,
        description: descInput?.value || '',
        debit: parseFloat(debitInput?.value) || 0,
        credit: parseFloat(creditInput?.value) || 0
    };
    
    // Details 데이터 추가 (details_modals.js에서 관리)
    if (typeof getDetailsDataForLine === 'function') {
        const detailsData = getDetailsDataForLine(lineIndex);
        
        // Debt Details 추가
        if (detailsData.debt) {
            lineData.debt = {
                counterparty_id: detailsData.debt.direction ? 
                    getCounterpartyFromLine(lineElement) : null,
                direction: detailsData.debt.direction,
                category: detailsData.debt.category,
                interest_rate: detailsData.debt.interest_rate || 0,
                interest_account_id: detailsData.debt.interest_account_id || null,
                interest_due_day: detailsData.debt.interest_due_day || null,
                issue_date: detailsData.debt.issue_date,
                due_date: detailsData.debt.due_date,
                description: detailsData.debt.description || '',
                linkedCounterparty_store_id: detailsData.debt.linkedCounterparty_store_id || null,
                counterparty_cash_location_id: detailsData.debt.counterparty_cash_location_id || null
            };
        }
        
        // Fixed Asset Details 추가
        if (detailsData.asset) {
            lineData.fix_asset = {
                asset_name: detailsData.asset.asset_name,
                acquisition_date: detailsData.asset.acquisition_date,
                useful_life_years: detailsData.asset.useful_life_years,
                salvage_value: detailsData.asset.salvage_value
            };
        }
    }
    
    // Cash Location 데이터 추가
    const cashLocationSelect = lineElement.querySelector('.cash-location-select');
    if (cashLocationSelect && cashLocationSelect.style.display !== 'none' && cashLocationSelect.value) {
        lineData.cash = {
            cash_location_id: cashLocationSelect.value
        };
    }
    
    // Counterparty 데이터 추가 (Details 없이도 기본 거래처 선택 지원)
    const counterpartySelect = lineElement.querySelector('.counterparty-select');
    if (counterpartySelect && counterpartySelect.value && !lineData.debt) {
        // Details가 없지만 거래처가 선택된 경우 기본 debt 구조 생성
        lineData.debt = {
            counterparty_id: counterpartySelect.value,
            direction: 'receivable', // 기본값
            category: 'trade', // 기본값
            issue_date: document.getElementById('entry_date')?.value || new Date().toISOString().slice(0, 10),
            due_date: addDaysToDate(document.getElementById('entry_date')?.value || new Date().toISOString().slice(0, 10), 30),
            description: lineData.description
        };
    }
    
    return lineData;
}

/**
 * 라인에서 거래처 ID 가져오기
 * @param {HTMLElement} lineElement 
 * @returns {string|null}
 */
function getCounterpartyFromLine(lineElement) {
    const counterpartySelect = lineElement.querySelector('.counterparty-select');
    return counterpartySelect?.value || null;
}

/**
 * 날짜에 일수 추가
 * @param {string} dateString 
 * @param {number} days 
 * @returns {string}
 */
function addDaysToDate(dateString, days) {
    const date = new Date(dateString);
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

/**
 * User ID 가져오기
 * @returns {string}
 */
function getUserId() {
    // PHP에서 설정된 값이나 URL 파라미터에서 확인
    const urlParams = new URLSearchParams(window.location.search);
    const urlUserId = urlParams.get('user_id');
    
    if (window.currentUserId) {
        return window.currentUserId;
    }
    
    if (urlUserId) {
        return urlUserId;
    }
    
    console.warn('User ID not found');
    return '';
}

/**
 * Journal Entry 저장
 */
async function saveJournalEntry() {
    try {
        const formData = collectFormData();
        
        // 기본 검증
        if (!formData.p_company_id) {
            throw new Error('Company ID is required');
        }
        
        if (!formData.p_lines || formData.p_lines.length < 2) {
            throw new Error('At least 2 journal lines are required');
        }
        
        // 차변/대변 균형 검증
        const totalDebit = formData.p_lines.reduce((sum, line) => sum + (line.debit || 0), 0);
        const totalCredit = formData.p_lines.reduce((sum, line) => sum + (line.credit || 0), 0);
        
        if (Math.abs(totalDebit - totalCredit) > 0.01) {
            throw new Error(`Debit and Credit must be equal. Debit: ${totalDebit}, Credit: ${totalCredit}`);
        }
        
        // 저장 버튼 비활성화
        const saveBtn = document.getElementById('save-btn');
        const originalText = saveBtn?.innerHTML;
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        }
        
        // 서버로 전송
        const response = await fetch('save_journal_entry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 성공 메시지 표시
            showSuccess('Journal entry saved successfully!');
            
            // 폼 초기화 (선택사항)
            if (confirm('Journal entry saved successfully! Do you want to create a new entry?')) {
                resetForm();
            }
        } else {
            throw new Error(result.error || 'Failed to save journal entry');
        }
        
    } catch (error) {
        console.error('Error saving journal entry:', error);
        showError(error.message);
    } finally {
        // 저장 버튼 복원
        const saveBtn = document.getElementById('save-btn');
        if (saveBtn && originalText) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }
}

/**
 * 폼 초기화
 */
function resetForm() {
    // 기본 정보 초기화
    document.getElementById('entry_date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('store_id').value = '';
    document.getElementById('description').value = '';
    // reference_number 관련 코드 제거됨
    
    // 모든 라인 제거 (첫 번째 2개 제외)
    const allLines = document.querySelectorAll('.journal-line');
    for (let i = allLines.length - 1; i >= 2; i--) {
        allLines[i].remove();
    }
    
    // 첫 번째 2개 라인 초기화
    allLines.forEach((line, index) => {
        if (index < 2) {
            // 계정 초기화
            const accountInput = line.querySelector('.account-search-input');
            const hiddenInput = line.querySelector('.account-id-hidden');
            if (accountInput) accountInput.value = '';
            if (hiddenInput) hiddenInput.value = '';
            
            // 금액 초기화
            const debitInput = line.querySelector('.debit-input');
            const creditInput = line.querySelector('.credit-input');
            if (debitInput) debitInput.value = '';
            if (creditInput) creditInput.value = '';
            
            // 설명 초기화
            const descInput = line.querySelector('input[name="line_description[]"]');
            if (descInput) descInput.value = '';
            
            // 확장 필드 숨기기
            if (typeof hideAllExtendedFields === 'function') {
                hideAllExtendedFields(line);
            }
        }
    });
    
    // Details 데이터 초기화
    if (typeof debtDetailsData !== 'undefined') {
        debtDetailsData = {};
    }
    if (typeof assetDetailsData !== 'undefined') {
        assetDetailsData = {};
    }
    
    // 라인 카운터 리셋
    lineCounter = 2;
    
    // 밸런스 업데이트
    if (typeof updateBalance === 'function') {
        updateBalance();
    }
    
    // 유효성 검사 업데이트
    if (typeof validateForm === 'function') {
        validateForm();
    }
}

/**
 * 성공 메시지 표시
 * @param {string} message 
 */
function showSuccess(message) {
    const successElement = document.getElementById('success-message');
    const successText = document.getElementById('success-text');
    
    if (successElement && successText) {
        successText.textContent = message;
        successElement.style.display = 'block';
        
        // 5초 후 자동 숨김
        setTimeout(() => {
            successElement.style.display = 'none';
        }, 5000);
    } else {
        // fallback으로 alert 사용
        alert('Success: ' + message);
    }
}

/**
 * 에러 메시지 표시
 * @param {string} message 
 */
function showError(message) {
    const errorElement = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    
    if (errorElement && errorText) {
        errorText.textContent = message;
        errorElement.style.display = 'block';
        
        // 5초 후 자동 숨김
        setTimeout(() => {
            errorElement.style.display = 'none';
        }, 5000);
    } else {
        // fallback으로 alert 사용
        alert('Error: ' + message);
    }
}
