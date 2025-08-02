/**
 * Journal Entry - Details Modals System
 * Debt Details와 Fixed Asset Details 모달 완전 구현
 */

// 전역 상태 관리
let currentModalLineElement = null;
let currentModalType = null;
let debtDetailsData = {};
let assetDetailsData = {};

/**
 * 채권/채무 상세 정보 모달 열기 (완전 구현)
 * @param {HTMLElement} lineElement 
 */
function openDebtDetailsModal(lineElement) {
    currentModalLineElement = lineElement;
    currentModalType = 'debt';
    
    const lineIndex = lineElement.dataset.lineIndex;
    const modalTitle = document.getElementById('advancedDetailsModalLabel');
    const modalBody = document.getElementById('modal-body-content');
    
    modalTitle.innerHTML = '<i class="bi bi-credit-card me-2"></i>Debt/Receivable Details';
    
    // 기존 데이터 로드 (있다면)
    const existingData = debtDetailsData[lineIndex] || {};
    
    modalBody.innerHTML = `
        <form id="debt-details-form">
            <div class="row">
                <!-- 좌측 컬럼: 기본 정보 -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Direction <span class="text-danger">*</span></label>
                                <select class="form-select" id="debt-direction" required>
                                    <option value="">Select Type</option>
                                    <option value="receivable" ${existingData.direction === 'receivable' ? 'selected' : ''}>Receivable (Money to be received)</option>
                                    <option value="payable" ${existingData.direction === 'payable' ? 'selected' : ''}>Payable (Money to be paid)</option>
                                </select>
                                <small class="form-text text-muted">Choose based on your company's perspective</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="debt-category" required>
                                    <option value="">Select Category</option>
                                    <option value="trade" ${existingData.category === 'trade' ? 'selected' : ''}>Trade (Sales/Purchase)</option>
                                    <option value="loan" ${existingData.category === 'loan' ? 'selected' : ''}>Loan (Borrowing/Lending)</option>
                                    <option value="other" ${existingData.category === 'other' ? 'selected' : ''}>Other (Miscellaneous)</option>
                                </select>
                                <small class="form-text text-muted">Trade for sales/purchases, Loan for borrowing</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="debt-issue-date" 
                                       value="${existingData.issue_date || new Date().toISOString().slice(0, 10)}" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="debt-due-date" 
                                       value="${existingData.due_date || ''}" required>
                                <div class="mt-2">
                                    <div class="btn-group" role="group" aria-label="Quick due dates">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDueDate(30)">30 Days</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDueDate(60)">60 Days</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDueDate(90)">90 Days</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" id="debt-description" rows="2" 
                                          placeholder="Additional notes about this debt...">${existingData.description || ''}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 우측 컬럼: 이자 및 고급 설정 -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bi bi-percent me-1"></i>Interest & Advanced Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Interest Rate (Annual %)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="debt-interest-rate" 
                                           step="0.01" min="0" max="100" placeholder="0.00"
                                           value="${existingData.interest_rate || ''}"
                                           onchange="calculateInterestPreview()">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="form-text text-muted">Annual interest rate (0 for no interest)</small>
                            </div>
                            
                            <div class="mb-3" id="interest-account-section" style="display: none;">
                                <label class="form-label">Interest Account</label>
                                <select class="form-select" id="debt-interest-account">
                                    <option value="">Select Interest Account</option>
                                    <!-- 이자 관련 계정들이 여기에 동적으로 로드됨 -->
                                </select>
                                <small class="form-text text-muted">Account for recording interest income/expense</small>
                            </div>
                            
                            <div class="mb-3" id="interest-due-day-section" style="display: none;">
                                <label class="form-label">Interest Due Day</label>
                                <select class="form-select" id="debt-interest-due-day">
                                    <option value="">Select Day</option>
                                    ${Array.from({length: 31}, (_, i) => {
                                        const day = i + 1;
                                        const selected = existingData.interest_due_day == day ? 'selected' : '';
                                        return `<option value="${day}" ${selected}>${day}</option>`;
                                    }).join('')}
                                </select>
                                <small class="form-text text-muted">Day of month when interest is due</small>
                            </div>
                            
                            <div class="mb-3" id="interest-preview" style="display: none;">
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-calculator me-1"></i>Interest Calculation Preview</h6>
                                    <div id="interest-calculation-details"></div>
                                </div>
                            </div>
                            
                            <!-- Internal Transaction Fields -->
                            <div id="internal-transaction-section" style="display: none;">
                                <hr>
                                <h6><i class="bi bi-building me-1"></i>Internal Transaction</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Linked Company Store</label>
                                    <select class="form-select" id="debt-linked-store" onchange="onLinkedStoreChange()">
                                        <option value="">Select Store</option>
                                        <!-- 내부 거래처의 점포들이 여기에 동적으로 로드됨 -->
                                    </select>
                                    <small class="form-text text-muted">Store within the linked company</small>
                                </div>
                                
                                <!-- Counterparty Cash Location 선택 필드 추가 -->
                                <div class="mb-3" id="counterparty-cash-location-container" style="display: block;">
                                    <label class="form-label">Counterparty Cash Location <span class="text-danger">*</span></label>
                                    <select class="form-select" id="counterparty-cash-location">
                                        <option value="">Select Cash Location</option>
                                    </select>
                                    <small class="form-text text-muted">Where the money will be received/paid by the counterparty</small>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <i class="bi bi-info-circle me-1"></i>
                                    <strong>Mirror Journal:</strong> A corresponding journal entry will be automatically created in the linked company's records.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 하단: 요약 정보 -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-card-checklist me-1"></i>Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Amount:</strong>
                                    <div id="debt-amount-display" class="text-primary">₩ 0</div>
                                </div>
                                <div class="col-md-3">
                                    <strong>Term:</strong>
                                    <div id="debt-term-display" class="text-info">- days</div>
                                </div>
                                <div class="col-md-3">
                                    <strong>Total Interest:</strong>
                                    <div id="debt-total-interest-display" class="text-success">₩ 0</div>
                                </div>
                                <div class="col-md-3">
                                    <strong>Status:</strong>
                                    <div id="debt-status-display" class="text-secondary">Draft</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // 모달 표시
    const modal = new bootstrap.Modal(document.getElementById('advancedDetailsModal'));
    modal.show();
    
    // 이벤트 리스너 설정
    setupDebtDetailsEventListeners();
    
    // 현재 금액 업데이트
    updateDebtAmountDisplay();
    
    // 이자 관련 계정 로드
    loadInterestAccounts();
    
    // 거래처가 internal인지 확인하고 internal section 표시
    checkAndShowInternalSection();
}

/**
 * 고정자산 상세 정보 모달 열기 (완전 구현)
 * @param {HTMLElement} lineElement 
 */
function openAssetDetailsModal(lineElement) {
    currentModalLineElement = lineElement;
    currentModalType = 'asset';
    
    const lineIndex = lineElement.dataset.lineIndex;
    const modalTitle = document.getElementById('advancedDetailsModalLabel');
    const modalBody = document.getElementById('modal-body-content');
    
    modalTitle.innerHTML = '<i class="bi bi-tools me-2"></i>Fixed Asset Details';
    
    // 기존 데이터 로드 (있다면)
    const existingData = assetDetailsData[lineIndex] || {};
    
    modalBody.innerHTML = `
        <form id="asset-details-form">
            <div class="row">
                <!-- 좌측 컬럼: 자산 정보 -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="bi bi-info-circle me-1"></i>Asset Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Asset Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="asset-name" 
                                       placeholder="e.g., Office Computer Set #3"
                                       value="${existingData.asset_name || ''}" required>
                                <small class="form-text text-muted">Descriptive name for the asset</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Acquisition Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="asset-acquisition-date" 
                                       value="${existingData.acquisition_date || new Date().toISOString().slice(0, 10)}" 
                                       required onchange="updateDepreciationPreview()">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Acquisition Cost <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₩</span>
                                    <input type="number" class="form-control" id="asset-acquisition-cost" 
                                           step="0.01" min="0" placeholder="0.00"
                                           value="${existingData.acquisition_cost || ''}" 
                                           required onchange="updateDepreciationPreview()">
                                </div>
                                <small class="form-text text-muted">Total cost including installation and setup</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Useful Life <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="asset-useful-life" 
                                           step="1" min="1" max="50" placeholder="5"
                                           value="${existingData.useful_life_years || ''}" 
                                           required onchange="updateDepreciationPreview()">
                                    <span class="input-group-text">years</span>
                                </div>
                                <div class="mt-2">
                                    <div class="btn-group" role="group" aria-label="Common useful life">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setUsefulLife(3)">3 years</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setUsefulLife(5)">5 years</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setUsefulLife(10)">10 years</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Expected useful life in years</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Salvage Value <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₩</span>
                                    <input type="number" class="form-control" id="asset-salvage-value" 
                                           step="0.01" min="0" placeholder="0.00"
                                           value="${existingData.salvage_value || ''}" 
                                           required onchange="updateDepreciationPreview()">
                                </div>
                                <div class="mt-2">
                                    <div class="btn-group" role="group" aria-label="Common salvage percentages">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSalvagePercentage(0)">0%</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSalvagePercentage(10)">10%</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setSalvagePercentage(20)">20%</button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Estimated value at end of useful life</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 우측 컬럼: 감가상각 미리보기 -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="bi bi-calculator me-1"></i>Depreciation Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Depreciation Method</label>
                                <select class="form-select" id="asset-depreciation-method" onchange="updateDepreciationPreview()">
                                    <option value="straight_line" ${existingData.depreciation_method === 'straight_line' ? 'selected' : ''}>Straight Line (Fixed Amount)</option>
                                    <option value="declining_balance" ${existingData.depreciation_method === 'declining_balance' ? 'selected' : ''}>Declining Balance (Fixed Rate)</option>
                                    <option value="double_declining" ${existingData.depreciation_method === 'double_declining' ? 'selected' : ''}>Double Declining (Double Fixed Rate)</option>
                                </select>
                                <small class="form-text text-muted">Most common is Straight Line method</small>
                            </div>
                            
                            <div id="depreciation-summary" class="alert alert-light">
                                <h6><i class="bi bi-graph-down me-1"></i>Summary</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Annual Depreciation:</strong>
                                        <div id="annual-depreciation" class="text-primary">₩ 0</div>
                                    </div>
                                    <div class="col-6">
                                        <strong>Monthly Depreciation:</strong>
                                        <div id="monthly-depreciation" class="text-success">₩ 0</div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <strong>Depreciable Amount:</strong>
                                        <div id="depreciable-amount" class="text-info">₩ 0</div>
                                    </div>
                                    <div class="col-6">
                                        <strong>Book Value (Year 1):</strong>
                                        <div id="book-value-year1" class="text-warning">₩ 0</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="depreciation-schedule" class="mt-3">
                                <h6><i class="bi bi-table me-1"></i>5-Year Schedule Preview</h6>
                                <div class="table-responsive" style="max-height: 200px;">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr>
                                                <th>Year</th>
                                                <th>Depreciation</th>
                                                <th>Book Value</th>
                                            </tr>
                                        </thead>
                                        <tbody id="depreciation-schedule-tbody">
                                            <!-- 감가상각 스케줄이 여기에 동적으로 생성됨 -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 하단: 회계 처리 정보 -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bi bi-journal-text me-1"></i>Accounting Treatment</h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-1"></i>Journal Entries that will be created:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Initial Purchase:</strong>
                                        <ul class="mb-0">
                                            <li>Dr. Fixed Assets ₩ <span id="journal-asset-amount">0</span></li>
                                            <li>Cr. Cash/Bank ₩ <span id="journal-payment-amount">0</span></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Monthly Depreciation:</strong>
                                        <ul class="mb-0">
                                            <li>Dr. Depreciation Expense ₩ <span id="journal-dep-expense">0</span></li>
                                            <li>Cr. Accumulated Depreciation ₩ <span id="journal-acc-dep">0</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    `;
    
    // 모달 표시
    const modal = new bootstrap.Modal(document.getElementById('advancedDetailsModal'));
    modal.show();
    
    // 이벤트 리스너 설정
    setupAssetDetailsEventListeners();
    
    // 초기 감가상각 미리보기 업데이트
    updateDepreciationPreview();
}

/**
 * Debt Details 이벤트 리스너 설정
 */
function setupDebtDetailsEventListeners() {
    // 이자율 변경 시 이자 관련 필드 표시/숨김
    const interestRateInput = document.getElementById('debt-interest-rate');
    if (interestRateInput) {
        interestRateInput.addEventListener('input', function() {
            const rate = parseFloat(this.value) || 0;
            
            const interestAccountSection = document.getElementById('interest-account-section');
            const interestDueDaySection = document.getElementById('interest-due-day-section');
            const interestPreviewSection = document.getElementById('interest-preview');
            
            if (rate > 0) {
                interestAccountSection.style.display = 'block';
                interestDueDaySection.style.display = 'block';
                interestPreviewSection.style.display = 'block';
                calculateInterestPreview();
            } else {
                interestAccountSection.style.display = 'none';
                interestDueDaySection.style.display = 'none';
                interestPreviewSection.style.display = 'none';
            }
        });
        
        // 초기 실행
        interestRateInput.dispatchEvent(new Event('input'));
    }
    
    // 날짜 변경 시 기간 계산 업데이트
    const issueDateInput = document.getElementById('debt-issue-date');
    const dueDateInput = document.getElementById('debt-due-date');
    
    [issueDateInput, dueDateInput].forEach(input => {
        if (input) {
            input.addEventListener('change', function() {
                updateDebtTermDisplay();
                calculateInterestPreview();
            });
        }
    });
}

/**
 * Asset Details 이벤트 리스너 설정
 */
function setupAssetDetailsEventListeners() {
    // 자산 가격 변경 시 회계 처리 금액 업데이트
    const costInput = document.getElementById('asset-acquisition-cost');
    if (costInput) {
        costInput.addEventListener('input', function() {
            updateJournalAmounts();
        });
        
        // 초기 실행
        updateJournalAmounts();
    }
}

/**
 * Quick Due Date 설정
 * @param {number} days 
 */
function setQuickDueDate(days) {
    const issueDateInput = document.getElementById('debt-issue-date');
    const dueDateInput = document.getElementById('debt-due-date');
    
    if (issueDateInput && dueDateInput) {
        const issueDate = new Date(issueDateInput.value);
        const dueDate = new Date(issueDate);
        dueDate.setDate(dueDate.getDate() + days);
        
        dueDateInput.value = dueDate.toISOString().slice(0, 10);
        updateDebtTermDisplay();
        calculateInterestPreview();
    }
}

/**
 * 유용연수 빠른 설정
 * @param {number} years 
 */
function setUsefulLife(years) {
    const usefulLifeInput = document.getElementById('asset-useful-life');
    if (usefulLifeInput) {
        usefulLifeInput.value = years;
        updateDepreciationPreview();
    }
}

/**
 * 잔존가치 백분율로 설정
 * @param {number} percentage 
 */
function setSalvagePercentage(percentage) {
    const costInput = document.getElementById('asset-acquisition-cost');
    const salvageInput = document.getElementById('asset-salvage-value');
    
    if (costInput && salvageInput) {
        const cost = parseFloat(costInput.value) || 0;
        const salvageValue = cost * (percentage / 100);
        salvageInput.value = salvageValue.toFixed(2);
        updateDepreciationPreview();
    }
}

/**
 * 채무 금액 표시 업데이트
 */
function updateDebtAmountDisplay() {
    if (!currentModalLineElement) return;
    
    const debitInput = currentModalLineElement.querySelector('.debit-input');
    const creditInput = currentModalLineElement.querySelector('.credit-input');
    
    const debit = parseFloat(debitInput?.value) || 0;
    const credit = parseFloat(creditInput?.value) || 0;
    const amount = Math.max(debit, credit);
    
    const display = document.getElementById('debt-amount-display');
    if (display) {
        display.textContent = '₩ ' + amount.toLocaleString();
    }
}

/**
 * 채무 기간 표시 업데이트
 */
function updateDebtTermDisplay() {
    const issueDateInput = document.getElementById('debt-issue-date');
    const dueDateInput = document.getElementById('debt-due-date');
    const display = document.getElementById('debt-term-display');
    
    if (issueDateInput && dueDateInput && display) {
        const issueDate = new Date(issueDateInput.value);
        const dueDate = new Date(dueDateInput.value);
        
        if (issueDate && dueDate && dueDate > issueDate) {
            const diffTime = dueDate - issueDate;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            display.textContent = diffDays + ' days';
        } else {
            display.textContent = '- days';
        }
    }
}

/**
 * 이자 미리보기 계산
 */
function calculateInterestPreview() {
    const rateInput = document.getElementById('debt-interest-rate');
    const issueDateInput = document.getElementById('debt-issue-date');
    const dueDateInput = document.getElementById('debt-due-date');
    const previewDiv = document.getElementById('interest-calculation-details');
    const totalInterestDisplay = document.getElementById('debt-total-interest-display');
    
    if (!rateInput || !issueDateInput || !dueDateInput || !previewDiv || !totalInterestDisplay) return;
    
    const rate = parseFloat(rateInput.value) || 0;
    const issueDate = new Date(issueDateInput.value);
    const dueDate = new Date(dueDateInput.value);
    
    if (rate > 0 && issueDate && dueDate && dueDate > issueDate) {
        // 현재 라인의 금액 가져오기
        const debitInput = currentModalLineElement?.querySelector('.debit-input');
        const creditInput = currentModalLineElement?.querySelector('.credit-input');
        const amount = Math.max(parseFloat(debitInput?.value) || 0, parseFloat(creditInput?.value) || 0);
        
        if (amount > 0) {
            const diffTime = dueDate - issueDate;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            const annualInterest = amount * (rate / 100);
            const totalInterest = annualInterest * (diffDays / 365);
            
            previewDiv.innerHTML = `
                <div class="row">
                    <div class="col-6">
                        <strong>Principal:</strong> ₩${amount.toLocaleString()}<br>
                        <strong>Rate:</strong> ${rate}% annual<br>
                        <strong>Term:</strong> ${diffDays} days
                    </div>
                    <div class="col-6">
                        <strong>Annual Interest:</strong> ₩${annualInterest.toLocaleString()}<br>
                        <strong>Daily Interest:</strong> ₩${(annualInterest/365).toLocaleString()}<br>
                        <strong>Total Interest:</strong> ₩${totalInterest.toLocaleString()}
                    </div>
                </div>
            `;
            
            totalInterestDisplay.textContent = '₩ ' + totalInterest.toLocaleString();
        } else {
            previewDiv.innerHTML = '<p class="text-muted">Enter amount in the journal line to calculate interest</p>';
            totalInterestDisplay.textContent = '₩ 0';
        }
    } else {
        previewDiv.innerHTML = '<p class="text-muted">Enter rate and dates to see calculation</p>';
        totalInterestDisplay.textContent = '₩ 0';
    }
}

/**
 * 감가상각 미리보기 업데이트
 */
function updateDepreciationPreview() {
    const costInput = document.getElementById('asset-acquisition-cost');
    const salvageInput = document.getElementById('asset-salvage-value');
    const usefulLifeInput = document.getElementById('asset-useful-life');
    const methodSelect = document.getElementById('asset-depreciation-method');
    
    if (!costInput || !salvageInput || !usefulLifeInput || !methodSelect) return;
    
    const cost = parseFloat(costInput.value) || 0;
    const salvage = parseFloat(salvageInput.value) || 0;
    const usefulLife = parseInt(usefulLifeInput.value) || 0;
    const method = methodSelect.value;
    
    if (cost > 0 && usefulLife > 0) {
        const depreciableAmount = cost - salvage;
        let annualDepreciation = 0;
        
        // 감가상각 방법에 따른 계산
        switch (method) {
            case 'straight_line':
                annualDepreciation = depreciableAmount / usefulLife;
                break;
            case 'declining_balance':
                annualDepreciation = cost * (1 / usefulLife); // 단순화된 계산
                break;
            case 'double_declining':
                annualDepreciation = cost * (2 / usefulLife); // 단순화된 계산
                break;
        }
        
        const monthlyDepreciation = annualDepreciation / 12;
        const bookValueYear1 = cost - annualDepreciation;
        
        // 요약 정보 업데이트
        document.getElementById('annual-depreciation').textContent = '₩ ' + annualDepreciation.toLocaleString();
        document.getElementById('monthly-depreciation').textContent = '₩ ' + monthlyDepreciation.toLocaleString();
        document.getElementById('depreciable-amount').textContent = '₩ ' + depreciableAmount.toLocaleString();
        document.getElementById('book-value-year1').textContent = '₩ ' + bookValueYear1.toLocaleString();
        
        // 감가상각 스케줄 생성
        generateDepreciationSchedule(cost, salvage, usefulLife, method);
    } else {
        // 값이 없으면 초기화
        document.getElementById('annual-depreciation').textContent = '₩ 0';
        document.getElementById('monthly-depreciation').textContent = '₩ 0';
        document.getElementById('depreciable-amount').textContent = '₩ 0';
        document.getElementById('book-value-year1').textContent = '₩ 0';
        
        const tbody = document.getElementById('depreciation-schedule-tbody');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Enter values to see schedule</td></tr>';
        }
    }
}

/**
 * 감가상각 스케줄 생성
 * @param {number} cost 
 * @param {number} salvage 
 * @param {number} usefulLife 
 * @param {string} method 
 */
function generateDepreciationSchedule(cost, salvage, usefulLife, method) {
    const tbody = document.getElementById('depreciation-schedule-tbody');
    if (!tbody) return;
    
    let html = '';
    let bookValue = cost;
    
    const maxYears = Math.min(usefulLife, 5); // Display up to 5 years maximum
    
    for (let year = 1; year <= maxYears; year++) {
        let depreciation = 0;
        
        switch (method) {
            case 'straight_line':
                depreciation = (cost - salvage) / usefulLife;
                break;
            case 'declining_balance':
                depreciation = bookValue * (1 / usefulLife);
                break;
            case 'double_declining':
                depreciation = Math.min(bookValue * (2 / usefulLife), bookValue - salvage);
                break;
        }
        
        bookValue = Math.max(bookValue - depreciation, salvage);
        
        html += `
            <tr>
                <td>${year}</td>
                <td>₩ ${depreciation.toLocaleString()}</td>
                <td>₩ ${bookValue.toLocaleString()}</td>
            </tr>
        `;
    }
    
    if (usefulLife > 5) {
        html += `
            <tr>
                <td colspan="3" class="text-center text-muted">
                    <i class="bi bi-three-dots"></i> ${usefulLife - 5} more years
                </td>
            </tr>
        `;
    }
    
    tbody.innerHTML = html;
}

/**
 * 회계 처리 금액 업데이트
 */
function updateJournalAmounts() {
    const costInput = document.getElementById('asset-acquisition-cost');
    const monthlyDepInput = document.getElementById('monthly-depreciation');
    
    if (costInput) {
        const cost = parseFloat(costInput.value) || 0;
        
        // 자산 취득 분개
        const assetAmountSpan = document.getElementById('journal-asset-amount');
        const paymentAmountSpan = document.getElementById('journal-payment-amount');
        
        if (assetAmountSpan) assetAmountSpan.textContent = cost.toLocaleString();
        if (paymentAmountSpan) paymentAmountSpan.textContent = cost.toLocaleString();
        
        // 월별 감가상각 분개
        if (monthlyDepInput) {
            const monthlyDep = parseFloat(monthlyDepInput.textContent.replace(/[₩,]/g, '')) || 0;
            
            const depExpenseSpan = document.getElementById('journal-dep-expense');
            const accDepSpan = document.getElementById('journal-acc-dep');
            
            if (depExpenseSpan) depExpenseSpan.textContent = monthlyDep.toLocaleString();
            if (accDepSpan) accDepSpan.textContent = monthlyDep.toLocaleString();
        }
    }
}

/**
 * 이자 관련 계정 로드
 */
async function loadInterestAccounts() {
    try {
        // 이자 관련 계정들을 필터링해서 로드
        const select = document.getElementById('debt-interest-account');
        if (!select) return;
        
        // accountsData에서 이자 관련 계정 찾기
        if (typeof accountsData !== 'undefined' && accountsData.length > 0) {
            const interestAccounts = accountsData.filter(account => 
                account.name.toLowerCase().includes('interest') ||
                account.name.toLowerCase().includes('이자')
            );
            
            select.innerHTML = '<option value="">Select Interest Account</option>';
            
            interestAccounts.forEach(account => {
                const option = document.createElement('option');
                option.value = account.id;
                option.textContent = account.name;
                select.appendChild(option);
            });
            
            // 이자 계정이 없으면 기본 옵션 추가
            if (interestAccounts.length === 0) {
                select.innerHTML += `
                    <option value="interest_income">Interest Income</option>
                    <option value="interest_expense">Interest Expense</option>
                `;
            }
        } else {
            // accountsData가 없으면 기본 옵션만 표시
            select.innerHTML = `
                <option value="">Select Interest Account</option>
                <option value="interest_income">Interest Income</option>
                <option value="interest_expense">Interest Expense</option>
            `;
        }
        
    } catch (error) {
        console.error('Error loading interest accounts:', error);
    }
}

/**
 * Internal section 표시 여부 확인
 */
function checkAndShowInternalSection() {
    if (!currentModalLineElement) return;
    
    const counterpartySelect = currentModalLineElement.querySelector('.counterparty-select');
    if (counterpartySelect && counterpartySelect.value) {
        const selectedOption = counterpartySelect.options[counterpartySelect.selectedIndex];
        const isInternal = selectedOption?.dataset.isInternal === 'true';
        
        const internalSection = document.getElementById('internal-transaction-section');
        if (internalSection) {
            internalSection.style.display = isInternal ? 'block' : 'none';
            
            if (isInternal) {
                // Internal counterparty가 선택된 경우 Linked Company Stores 로드
                loadLinkedCompanyStores(counterpartySelect.value);
                // Cash Location 섹션을 미리 표시 (Store 선택 전에도)
                loadCounterpartyCashLocationsForModal();
            }
        }
    }
}

/**
 * 고급 상세 정보 저장
 */
function saveAdvancedDetails() {
    if (!currentModalLineElement || !currentModalType) {
        alert('Error: No line element found');
        return;
    }
    
    const lineIndex = currentModalLineElement.dataset.lineIndex;
    
    if (currentModalType === 'debt') {
        // Debt details 저장
        const formData = {
            direction: document.getElementById('debt-direction')?.value,
            category: document.getElementById('debt-category')?.value,
            issue_date: document.getElementById('debt-issue-date')?.value,
            due_date: document.getElementById('debt-due-date')?.value,
            description: document.getElementById('debt-description')?.value,
            interest_rate: parseFloat(document.getElementById('debt-interest-rate')?.value) || 0,
            interest_account_id: document.getElementById('debt-interest-account')?.value,
            interest_due_day: parseInt(document.getElementById('debt-interest-due-day')?.value) || null,
            linkedCounterparty_store_id: document.getElementById('debt-linked-store')?.value,
            counterparty_cash_location_id: document.getElementById('counterparty-cash-location')?.value || null
        };
        
        // 필수 필드 검증
        if (!formData.direction || !formData.category || !formData.issue_date || !formData.due_date) {
            alert('Please fill in all required fields (Direction, Category, Issue Date, Due Date)');
            return;
        }
        
        debtDetailsData[lineIndex] = formData;
        
        // 라인에 저장된 데이터 표시
        const detailsButton = currentModalLineElement.querySelector('.debt-details-button');
        if (detailsButton) {
            detailsButton.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>Debt Details';
            detailsButton.classList.remove('btn-outline-info');
            detailsButton.classList.add('btn-outline-success');
        }
        
    } else if (currentModalType === 'asset') {
        // Asset details 저장
        const formData = {
            asset_name: document.getElementById('asset-name')?.value,
            acquisition_date: document.getElementById('asset-acquisition-date')?.value,
            acquisition_cost: parseFloat(document.getElementById('asset-acquisition-cost')?.value) || 0,
            useful_life_years: parseInt(document.getElementById('asset-useful-life')?.value) || 0,
            salvage_value: parseFloat(document.getElementById('asset-salvage-value')?.value) || 0,
            depreciation_method: document.getElementById('asset-depreciation-method')?.value
        };
        
        // 필수 필드 검증
        if (!formData.asset_name || !formData.acquisition_date || !formData.acquisition_cost || !formData.useful_life_years) {
            alert('Please fill in all required fields (Asset Name, Acquisition Date, Cost, Useful Life)');
            return;
        }
        
        assetDetailsData[lineIndex] = formData;
        
        // 라인에 저장된 데이터 표시
        const detailsButton = currentModalLineElement.querySelector('.asset-details-button');
        if (detailsButton) {
            detailsButton.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i>Asset Details';
            detailsButton.classList.remove('btn-outline-warning');
            detailsButton.classList.add('btn-outline-success');
        }
    }
    
    // 모달 닫기
    const modal = bootstrap.Modal.getInstance(document.getElementById('advancedDetailsModal'));
    if (modal) {
        modal.hide();
    }
    
    // 성공 메시지
    if (typeof showSuccess === 'function') {
        showSuccess(`${currentModalType === 'debt' ? 'Debt' : 'Asset'} details saved successfully!`);
    } else {
        // fallback 메시지
        console.log(`${currentModalType === 'debt' ? 'Debt' : 'Asset'} details saved successfully!`);
    }
    
    // 전역 상태 초기화
    currentModalLineElement = null;
    currentModalType = null;
}

/**
 * Linked Store 변경 시 Cash Location 로드
 */
function onLinkedStoreChange() {
    const storeSelect = document.getElementById('debt-linked-store');
    const cashLocationContainer = document.getElementById('counterparty-cash-location-container');
    const cashLocationSelect = document.getElementById('counterparty-cash-location');
    
    if (!storeSelect || !cashLocationContainer || !cashLocationSelect) return;
    
    const selectedStoreId = storeSelect.value;
    
    if (selectedStoreId) {
        // Store가 선택되면 Cash Location 섹션 표시
        cashLocationContainer.style.display = 'block';
        
        // 선택된 Store의 linked_company_id 가져오기
        const selectedOption = storeSelect.options[storeSelect.selectedIndex];
        const linkedCompanyId = selectedOption?.dataset.linkedCompanyId;
        
        if (linkedCompanyId) {
            loadCounterpartyCashLocations(linkedCompanyId, selectedStoreId);
        }
    } else {
        // Store가 선택되지 않으면 Cash Location 섹션 숨김
        cashLocationContainer.style.display = 'none';
        cashLocationSelect.innerHTML = '<option value="">Select Cash Location</option>';
    }
}

/**
 * Counterparty Cash Location 로드
 * @param {string} linkedCompanyId 
 * @param {string} storeId 
 */
async function loadCounterpartyCashLocations(linkedCompanyId, storeId = null) {
    try {
        const params = new URLSearchParams({
            linked_company_id: linkedCompanyId
        });
        if (storeId) params.append('store_id', storeId);
        
        const response = await fetch(`get_counterparty_cash_locations.php?${params}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            populateCounterpartyCashLocationDropdown(result.data);
        } else {
            console.error('Failed to load cash locations:', result.error || 'Unknown error');
            // Fallback: 기본 옵션만 표시
            populateCounterpartyCashLocationDropdown([]);
        }
        
    } catch (error) {
        console.error('Error loading counterparty cash locations:', error);
        // Fallback: 기본 옵션만 표시
        populateCounterpartyCashLocationDropdown([]);
    }
}

/**
 * Cash Location 드롭다운 채우기
 * @param {Array} locations 
 */
function populateCounterpartyCashLocationDropdown(locations) {
    const select = document.getElementById('counterparty-cash-location');
    if (!select) return;
    
    select.innerHTML = '<option value="">Select Cash Location</option>';
    
    if (locations && locations.length > 0) {
        locations.forEach(location => {
            const option = document.createElement('option');
            option.value = location.cash_location_id;
            option.textContent = `${location.icon || '💰'} ${location.location_name}`;
            if (location.store_name) {
                option.textContent += ` (${location.store_name})`;
            }
            select.appendChild(option);
        });
    } else {
        // 데이터가 없으면 기본 메시지 표시
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No cash locations available';
        option.disabled = true;
        select.appendChild(option);
    }
}

/**
 * Linked Company Stores 로드
 * @param {string} counterpartyId 
 */
async function loadLinkedCompanyStores(counterpartyId) {
    try {
        // counterpartiesData에서 linked_company_id 찾기
        let linkedCompanyId = null;
        
        if (typeof counterpartiesData !== 'undefined' && counterpartiesData.length > 0) {
            const counterparty = counterpartiesData.find(c => c.counterparty_id === counterpartyId);
            if (counterparty && counterparty.linked_company_id) {
                linkedCompanyId = counterparty.linked_company_id;
            }
        }
        
        if (!linkedCompanyId) {
            console.warn('No linked company found for counterparty:', counterpartyId);
            populateLinkedStoresDropdown([]);
            return;
        }
        
        // Linked company의 stores 로드
        const response = await fetch(`get_linked_company_stores.php?linked_company_id=${encodeURIComponent(linkedCompanyId)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            populateLinkedStoresDropdown(result.data);
        } else {
            console.error('Failed to load linked company stores:', result.error || 'Unknown error');
            // Fallback: 기본 옵션만 표시
            populateLinkedStoresDropdown([]);
        }
        
    } catch (error) {
        console.error('Error loading linked company stores:', error);
        // Fallback: 기본 옵션만 표시
        populateLinkedStoresDropdown([]);
    }
}

/**
 * Linked Stores 드롭다운 채우기
 * @param {Array} stores 
 */
function populateLinkedStoresDropdown(stores) {
    const select = document.getElementById('debt-linked-store');
    if (!select) return;
    
    select.innerHTML = '<option value="">Select Store</option>';
    
    if (stores && stores.length > 0) {
        stores.forEach(store => {
            const option = document.createElement('option');
            option.value = store.store_id;
            option.textContent = store.store_name;
            option.dataset.linkedCompanyId = store.company_id;
            select.appendChild(option);
        });
    } else {
        // 데이터가 없으면 기본 메시지 표시
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No stores available';
        option.disabled = true;
        select.appendChild(option);
    }
}

/**
 * Modal에서 Counterparty Cash Locations 로드 (전체 linked company로부터)
 */
async function loadCounterpartyCashLocationsForModal() {
    try {
        if (!currentModalLineElement) return;
        
        const counterpartySelect = currentModalLineElement.querySelector('.counterparty-select');
        if (!counterpartySelect || !counterpartySelect.value) return;
        
        // counterpartiesData에서 linked_company_id 찾기
        let linkedCompanyId = null;
        
        if (typeof counterpartiesData !== 'undefined' && counterpartiesData.length > 0) {
            const counterparty = counterpartiesData.find(c => c.counterparty_id === counterpartySelect.value);
            if (counterparty && counterparty.linked_company_id) {
                linkedCompanyId = counterparty.linked_company_id;
            }
        }
        
        if (!linkedCompanyId) {
            console.warn('No linked company found for counterparty:', counterpartySelect.value);
            populateCounterpartyCashLocationDropdown([]);
            return;
        }
        
        const params = new URLSearchParams({
            linked_company_id: linkedCompanyId
        });
        
        const response = await fetch(`get_counterparty_cash_locations.php?${params}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            populateCounterpartyCashLocationDropdown(result.data);
        } else {
            console.error('Failed to load cash locations:', result.error || 'Unknown error');
            // Fallback: 기본 옵션만 표시
            populateCounterpartyCashLocationDropdown([]);
        }
        
    } catch (error) {
        console.error('Error loading counterparty cash locations for modal:', error);
        // Fallback: 기본 옵션만 표시
        populateCounterpartyCashLocationDropdown([]);
    }
}

/**
 * Details 데이터 가져오기 (저장 시 사용)
 * @param {number} lineIndex 
 * @returns {object}
 */
function getDetailsDataForLine(lineIndex) {
    return {
        debt: debtDetailsData[lineIndex] || null,
        asset: assetDetailsData[lineIndex] || null
    };
}
