/**
 * Journal Entry - Core Line Management Functions
 * Essential functions for adding, removing, and managing journal lines
 */

// Global variables
let lineCounter = 2; // Start with 2 since we have initial lines
let cashLocationsData = []; // 💰 Cache for cash locations
let counterpartiesData = []; // 🏢 Cache for counterparties/companies

// 🔍 Get Company ID from various sources
function getCompanyId() {
    // 1. URL 파라미터에서 company_id 확인
    const urlParams = new URLSearchParams(window.location.search);
    const urlCompanyId = urlParams.get('company_id');
    
    // 2. 글로벌 변수에서 확인 (PHP에서 설정)
    if (typeof companyId !== 'undefined' && companyId) {
        return companyId;
    }
    
    // 3. window 객체에서 확인
    if (window.companyId) {
        return window.companyId;
    }
    
    // 4. URL 파라미터 반환
    if (urlCompanyId) {
        return urlCompanyId;
    }
    
    console.warn('Company ID not found in any source');
    return null;
}

// 💰 Cash Related Account Mapping (계정 ID 기반)
const cashRelatedAccounts = {
    'cash': ['d4a7a16e-45a1-47fe-992b-ff807c8673f0'], // Cash account ID
    'bank': ['f0e7baca-c465-4efe-9b5a-cbb942caaf49'], // Bank Account ID
    'vault': [] // Vault 관련 계정 (필요시 추가)
};

// 💰 Load cash locations for current company and store
async function loadCashLocations(storeId = null) {
    const companyId = getCompanyId();
    if (!companyId) {
        console.warn('No company ID available for cash locations');
        return [];
    }
    
    try {
        let url = `get_cash_locations.php?company_id=${companyId}&user_id=${window.userId}`;
        if (storeId) {
            url += `&store_id=${storeId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            const locations = data.data || [];
            // Store in global variable only if no specific store requested (for backward compatibility)
            if (!storeId) {
                cashLocationsData = locations;
                window.cashLocationsData = cashLocationsData;
            }
            console.log('Cash locations loaded:', locations.length, 'locations', storeId ? `for store ${storeId}` : 'for company');
            return locations;
        } else {
            console.error('Failed to load cash locations:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error loading cash locations:', error);
        return [];
    }
}

// 🏢 Load counterparties for current company
async function loadCounterparties() {
    const companyId = getCompanyId();
    if (!companyId) {
        console.warn('No company ID available for counterparties');
        return [];
    }
    
    try {
        const response = await fetch(`get_counterparties.php?company_id=${companyId}&user_id=${window.userId}`);
        const data = await response.json();
        
        if (data.success) {
            counterpartiesData = data.data || [];
            window.counterpartiesData = counterpartiesData; // Also store in window for global access
            console.log('Counterparties loaded:', counterpartiesData.length, 'counterparties');
            return counterpartiesData;
        } else {
            console.error('Failed to load counterparties:', data.error);
            return [];
        }
    } catch (error) {
        console.error('Error loading counterparties:', error);
        return [];
    }
}

// 🔍 Detect cash account type by account ID and category tag
function detectCashAccountType(accountId, categoryTag) {
    if (!accountId) return null;
    
    // Cash 계정만 감지 (category_tag 기반)
    if (categoryTag === 'cash') {
        return 'cash';
    }
    
    // 또는 하드코딩된 Cash 계정 ID로 확인
    if (accountId === 'd4a7a16e-45a1-47fe-992b-ff807c8673f0') {
        return 'cash';
    }
    
    return null;
}

// 🏪 Get currently selected store ID
function getSelectedStoreId() {
    const storeSelect = document.querySelector('[name="store_id"]');
    return storeSelect ? storeSelect.value : null;
}

// 💰 Show cash location selector for cash/bank accounts
async function showCashLocationSelector(lineElement, accountType) {
    // Check if store is selected
    const storeId = getSelectedStoreId();
    
    // Get location cell (6th column, index 5)
    const locationContainer = lineElement.cells[5]; // 0-based index
    
    if (!locationContainer) {
        console.error('Location container not found - cell index 5');
        return;
    }
    
    // Add class for styling
    locationContainer.classList.add('location-selector-container');
    locationContainer.style.display = 'table-cell';
    
    // If no store is selected, show simplified message
    if (!storeId) {
        locationContainer.innerHTML = `
            <select class="form-select form-select-sm cash-location-select" disabled>
                <option value="">Select Store First</option>
            </select>
        `;
        console.log('Store not selected - showing store selection message');
        return;
    }
    
    try {
        // Load cash locations for the selected store
        const locations = await loadCashLocations(storeId);
        
        // Filter locations by type
        const filteredLocations = locations.filter(location => {
            if (accountType === 'cash') {
                return location.location_type === 'cash' || location.location_type === 'vault';
            } else if (accountType === 'bank') {
                return location.location_type === 'bank';
            }
            return false;
        });
        
        // Create select element
        let selectHtml = '<select class="form-select form-select-sm cash-location-select">';
        selectHtml += '<option value="">Select Location</option>';
        
        filteredLocations.forEach(location => {
            selectHtml += `<option value="${location.cash_location_id}">${location.display_name}</option>`;
        });
        
        selectHtml += '</select>';
        
        // Store 선택 후에는 불필요한 안내 메시지 제거
        
        locationContainer.innerHTML = selectHtml;
        
        console.log(`Showing ${filteredLocations.length} ${accountType} locations for store ${storeId}`);
        
    } catch (error) {
        console.error('Error loading cash locations:', error);
        locationContainer.innerHTML = `
            <select class="form-select form-select-sm cash-location-select" disabled>
                <option value="">Error Loading Locations</option>
            </select>
        `;
    }
}

// 🙈 Hide cash location selector
function hideCashLocationSelector(lineElement) {
    const locationContainer = lineElement.cells[5]; // 0-based index
    if (locationContainer) {
        // Clear the container content
        locationContainer.innerHTML = '';
        locationContainer.classList.remove('location-selector-container');
    }
}

// 🎯 Handle account selection (triggers cash location detection)
async function onAccountSelected(accountId, lineElement, categoryTag) {
    console.log('Account selected:', accountId, 'Category Tag:', categoryTag);
    
    const accountType = detectCashAccountType(accountId, categoryTag);
    console.log('Detected account type:', accountType);
    
    if (accountType === 'cash') {
        console.log('Activating cash location selector...');
        await showCashLocationSelector(lineElement, 'cash');
        console.log('Cash 계정 선택됨 - Location 활성화');
    } else {
        console.log('Hiding cash location selector...');
        hideCashLocationSelector(lineElement);
        console.log('일반 계정 선택됨 - Location 비활성화');
    }
}

// 🔍 Get company ID from URL parameters
function getCompanyId() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('company_id');
}

// 📝 Add new journal line
function addJournalLine() {
    console.log('Add journal line triggered');
    
    lineCounter++;
    const tbody = document.getElementById('journal-lines-tbody');
    
    if (!tbody) {
        console.error('Journal lines tbody not found');
        return;
    }
    
    // Create new row HTML
    const newRow = document.createElement('tr');
    newRow.className = 'journal-line';
    newRow.setAttribute('data-line-index', lineCounter);
    
    newRow.innerHTML = `
        <td class="text-center line-number">${lineCounter}</td>
        <td>
            <div class="account-dropdown-container">
                <input type="text" class="form-control form-control-sm account-search-input" 
                       placeholder="Search accounts..." 
                       onkeyup="filterAccounts(this)" 
                       onblur="hideAccountDropdown(this)"
                       onfocus="showAccountDropdown(this)"
                       autocomplete="off">
                <input type="hidden" class="account-id-hidden" name="account_id[]">
                <div class="account-dropdown-list" style="display: none;">
                    <!-- 동적으로 계정 목록 생성 -->
                </div>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Enter description" onchange="validateForm()">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm debit-input" name="debit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance(); validateForm()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm credit-input" name="credit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance(); validateForm()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
        </td>
        <td class="location-selector-container">
            <select class="form-select form-select-sm cash-location-select">
                <option value="">Select Location</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" onclick="removeJournalLine(${lineCounter})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    // Append to tbody
    tbody.appendChild(newRow);
    
    // Update remove buttons
    updateRemoveButtons();
    
    // Update line numbering
    updateLineNumbers();
    
    // Focus on the new account search input
    const newAccountInput = newRow.querySelector('.account-search-input');
    if (newAccountInput) {
        setTimeout(() => {
            newAccountInput.focus();
        }, 100);
    }
    
    console.log(`Added new journal line #${lineCounter}`);
}

// 🗑️ Remove journal line
function removeJournalLine(lineIndex) {
    console.log(`Remove journal line ${lineIndex} triggered`);
    
    const lineRow = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
    if (!lineRow) {
        console.error(`Line ${lineIndex} not found`);
        return;
    }
    
    // Don't allow removing if only 2 lines remain
    const totalLines = document.querySelectorAll('.journal-line').length;
    if (totalLines <= 2) {
        showError('At least 2 journal lines are required');
        return;
    }
    
    // Remove the line
    lineRow.remove();
    
    // Update line numbers
    updateLineNumbers();
    
    // Update remove buttons
    updateRemoveButtons();
    
    // Update balance
    updateBalance();
    
    console.log(`Removed journal line #${lineIndex}`);
}

// 🔢 Update line numbers
function updateLineNumbers() {
    const lines = document.querySelectorAll('.journal-line');
    lines.forEach((line, index) => {
        const lineNumber = index + 1;
        
        // Update line number display
        const lineNumberCell = line.querySelector('.line-number');
        if (lineNumberCell) {
            lineNumberCell.textContent = lineNumber;
        }
        
        // Update data attribute
        line.setAttribute('data-line-index', lineNumber);
        
        // Update remove button onclick
        const removeBtn = line.querySelector('.remove-line-btn');
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeJournalLine(${lineNumber})`);
        }
        
        // Note: Details 버튼이 제거되었으므로 관련 코드도 제거
    });
}

// 🔧 Update remove buttons state
function updateRemoveButtons() {
    const removeButtons = document.querySelectorAll('.remove-line-btn');
    const lineCount = document.querySelectorAll('.journal-line').length;
    
    removeButtons.forEach(btn => {
        btn.disabled = lineCount <= 2;
        if (lineCount <= 2) {
            btn.title = 'At least 2 lines required';
        } else {
            btn.title = 'Remove this line';
        }
    });
}

// 🔄 Handle debit/credit input (prevent both having values)
function handleDebitCreditInput(input) {
    const line = input.closest('.journal-line');
    const debitInput = line.querySelector('.debit-input');
    const creditInput = line.querySelector('.credit-input');
    
    if (input === debitInput && parseFloat(input.value) > 0) {
        creditInput.value = '';
    } else if (input === creditInput && parseFloat(input.value) > 0) {
        debitInput.value = '';
    }
    
    // Update balance immediately
    updateBalance();
}

// 💰 Update balance calculation
function updateBalance() {
    const lines = document.querySelectorAll('.journal-line');
    let totalDebit = 0;
    let totalCredit = 0;
    
    lines.forEach(line => {
        const debitInput = line.querySelector('.debit-input');
        const creditInput = line.querySelector('.credit-input');
        
        const debitAmount = parseFloat(debitInput.value) || 0;
        const creditAmount = parseFloat(creditInput.value) || 0;
        
        totalDebit += debitAmount;
        totalCredit += creditAmount;
    });
    
    // Update display values
    const debitDisplay = document.querySelector('.balance-info .text-primary');
    const creditDisplay = document.querySelector('.balance-info .text-success');
    const differenceDisplay = document.querySelector('.balance-info .text-muted');
    const balanceCheck = document.getElementById('balance-check');
    const balanceStatus = document.getElementById('balance-status');
    
    if (debitDisplay) debitDisplay.textContent = '₫ ' + totalDebit.toLocaleString('vi-VN', {minimumFractionDigits: 2});
    if (creditDisplay) creditDisplay.textContent = '₫ ' + totalCredit.toLocaleString('vi-VN', {minimumFractionDigits: 2});
    
    const difference = Math.abs(totalDebit - totalCredit);
    if (differenceDisplay) differenceDisplay.textContent = '₫ ' + difference.toLocaleString('vi-VN', {minimumFractionDigits: 2});
    
    // Update balance status
    const isBalanced = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;
    
    if (balanceCheck) {
        balanceCheck.checked = isBalanced;
        balanceCheck.disabled = !isBalanced;
    }
    
    if (balanceStatus) {
        if (isBalanced) {
            balanceStatus.textContent = 'Entry is balanced';
            balanceStatus.className = 'text-success';
            if (differenceDisplay) differenceDisplay.className = 'text-success fw-bold';
        } else if (totalDebit === 0 && totalCredit === 0) {
            balanceStatus.textContent = 'Enter amounts to verify balance';
            balanceStatus.className = 'text-muted';
            if (differenceDisplay) differenceDisplay.className = 'text-muted fw-bold';
        } else {
            balanceStatus.textContent = 'Entry is not balanced';
            balanceStatus.className = 'text-danger';
            if (differenceDisplay) differenceDisplay.className = 'text-danger fw-bold';
        }
    }
    
    // Trigger form validation
    validateForm();
}

// ✅ Validate form (Enhanced with Cash Location validation)
function validateForm() {
    const lines = document.querySelectorAll('.journal-line');
    const saveBtn = document.getElementById('save-btn');
    
    let hasValidLines = 0;
    let isBalanced = true;
    let totalDebit = 0;
    let totalCredit = 0;
    let cashLocationErrors = [];
    
    // Check each line
    lines.forEach((line, index) => {
        const hiddenInput = line.querySelector('.account-id-hidden');
        const debitInput = line.querySelector('.debit-input');
        const creditInput = line.querySelector('.credit-input');
        const locationSelect = line.querySelector('.cash-location-select');
        
        const hasAccount = hiddenInput && hiddenInput.value !== '';
        const debitAmount = parseFloat(debitInput.value) || 0;
        const creditAmount = parseFloat(creditInput.value) || 0;
        const hasAmount = debitAmount > 0 || creditAmount > 0;
        
        if (hasAccount && hasAmount) {
            hasValidLines++;
            totalDebit += debitAmount;
            totalCredit += creditAmount;
            
            // ✨ Cash Location 검증 추가
            const accountId = hiddenInput.value;
            if (isCashAccount(accountId)) {
                const locationVisible = locationSelect && 
                    window.getComputedStyle(locationSelect.closest('.location-selector-container')).display !== 'none';
                
                if (locationVisible && (!locationSelect.value || locationSelect.value === '')) {
                    cashLocationErrors.push(index + 1);
                    // ✨ 실시간 경고 표시
                    showLocationWarning(line, true);
                } else if (locationSelect && locationSelect.value) {
                    // ✨ 경고 제거 (올바르게 선택됨)
                    showLocationWarning(line, false);
                }
            }
        }
    });
    
    // Check if balanced
    isBalanced = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;
    
    // ✨ Cash Location 오류가 있으면 폼 무효화
    const isFormValid = hasValidLines >= 2 && isBalanced && cashLocationErrors.length === 0;
    
    if (saveBtn) {
        saveBtn.disabled = !isFormValid;
        if (isFormValid) {
            saveBtn.classList.remove('btn-outline-primary');
            saveBtn.classList.add('btn-primary');
        } else {
            saveBtn.classList.remove('btn-primary');
            saveBtn.classList.add('btn-outline-primary');
        }
    }
    
    // ✨ Cash Location 오류 메시지 표시
    if (cashLocationErrors.length > 0) {
        console.warn(`Cash location required for lines: ${cashLocationErrors.join(', ')}`);
    }
    
    return isFormValid;
}

// ✨ Cash 계정 확인 함수 (기존 isCashAccount 함수 재사용)
function isCashAccount(accountId) {
    return accountId === 'd4a7a16e-45a1-47fe-992b-ff807c8673f0'; // Cash 계정 ID
}

// 📝 Reset form to initial state
function resetForm() {
    if (!confirm('Are you sure you want to reset the form? All unsaved data will be lost.')) {
        return;
    }
    
    // Reset basic fields
    document.getElementById('entry_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('store_id').value = '';
    // reference_number 관련 코드 제거됨
    document.getElementById('description').value = '';
    
    // Clear all lines and reset to 2
    clearAllJournalLines();
    
    // Update balance and validation
    updateBalance();
    
    // Hide messages
    hideMessages();
    
    // Reset editing state
    window.editingEntryId = null;
    const saveBtn = document.getElementById('save-btn');
    if (saveBtn) {
        saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Journal Entry';
        saveBtn.classList.remove('btn-warning');
        saveBtn.classList.add('btn-primary');
    }
}

// 📋 Clear all journal lines and reset to initial state
function clearAllJournalLines() {
    // Remove all existing lines
    const tbody = document.getElementById('journal-lines-tbody');
    tbody.innerHTML = '';
    
    // Reset line counter
    lineCounter = 0;
    
    // Add initial 2 lines
    for (let i = 1; i <= 2; i++) {
        addJournalLine();
    }
    
    // Update remove buttons
    updateRemoveButtons();
}

// 💾 Save journal entry
function saveJournalEntry() {
    console.log('Save journal entry triggered');
    
    // Validate form first
    if (!validateForm()) {
        showError('Please complete all required fields and ensure the entry is balanced.');
        return;
    }
    
    // Collect form data
    const formData = {
        company_id: getCompanyId(), // Add company_id
        entry_date: document.getElementById('entry_date').value,
        store_id: document.getElementById('store_id').value,
        // reference_number 제거됨
        description: document.getElementById('description').value,
        lines: getJournalLines() // Change journal_lines to lines
    };
    
    // Add editing mode if applicable
    if (window.editingEntryId) {
        formData.journal_id = window.editingEntryId;
    }
    
    // Disable save button and show loading
    const saveBtn = document.getElementById('save-btn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
    
    // Send to server
    fetch('save_journal_entry.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(window.editingEntryId ? 'Journal entry updated successfully!' : 'Journal entry saved successfully!');
            
            // Reset form if it was a new entry
            if (!window.editingEntryId) {
                setTimeout(() => {
                    resetForm();
                }, 2000);
            }
        } else {
            showError(data.error || 'Failed to save journal entry');
        }
    })
    .catch(error => {
        console.error('Error saving journal entry:', error);
        showError('Network error occurred while saving.');
    })
    .finally(() => {
        // Restore save button
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

// 📊 Get current journal lines data
function getJournalLines() {
    const lines = [];
    const lineElements = document.querySelectorAll('.journal-line');
    
    lineElements.forEach(line => {
        const hiddenInput = line.querySelector('.account-id-hidden');
        const debitInput = line.querySelector('.debit-input');
        const creditInput = line.querySelector('.credit-input');
        const descInput = line.querySelector('input[name="line_description[]"]');
        const cashLocationSelect = line.querySelector('.cash-location-select');
        
        const accountId = hiddenInput ? hiddenInput.value : '';
        const debitAmount = parseFloat(debitInput.value) || 0;
        const creditAmount = parseFloat(creditInput.value) || 0;
        const description = descInput ? descInput.value : '';
        const cashLocationId = cashLocationSelect ? cashLocationSelect.value : '';
        
        if (accountId && (debitAmount > 0 || creditAmount > 0)) {
            const lineData = {
                account_id: accountId,
                description: description,
                debit_amount: debitAmount,  // 백엔드 호환성을 위해 기존 key 유지
                credit_amount: creditAmount // 백엔드 호환성을 위해 기존 key 유지
            };
            
            // 💰 Phase 4: Add basic cash location information if selected (and no advanced details)
            if (cashLocationId && !line.getAttribute('data-advanced-details')) {
                lineData.cash = {
                    cash_location_id: cashLocationId
                };
            }
            
            // 🚀 Phase 5: Add advanced details information
            const advancedDetailsStr = line.getAttribute('data-advanced-details');
            if (advancedDetailsStr) {
                try {
                    const advancedDetails = JSON.parse(advancedDetailsStr);
                    const lineType = line.getAttribute('data-line-type');
                    
                    if (lineType === 'cash' && advancedDetails.cash_location_id) {
                        // Enhanced cash information from modal
                        lineData.cash = {
                            cash_location_id: advancedDetails.cash_location_id,
                            currency_code: advancedDetails.currency_code,
                            exchange_rate: advancedDetails.exchange_rate,
                            // reference_number 제거됨
                            notes: advancedDetails.notes
                        };
                    } else if (lineType === 'debt') {
                        lineData.debt = {
                            direction: advancedDetails.direction,
                            category: advancedDetails.category,
                            due_date: advancedDetails.due_date,
                            interest_rate: advancedDetails.interest_rate,
                            description: advancedDetails.description
                        };
                    } else if (lineType === 'asset') {
                        lineData.fix_asset = {
                            asset_name: advancedDetails.asset_name,
                            acquisition_date: advancedDetails.acquisition_date,
                            useful_life_years: advancedDetails.useful_life_years,
                            salvage_value: advancedDetails.salvage_value,
                            depreciation_method: advancedDetails.depreciation_method,
                            description: advancedDetails.description
                        };
                    }
                } catch (error) {
                    console.warn('Failed to parse advanced details for line:', error);
                }
            }
            
            lines.push(lineData);
        }
    });
    
    return lines;
}

// 🎯 Show success message
function showSuccess(message) {
    hideMessages();
    const successDiv = document.getElementById('success-message');
    const successText = document.getElementById('success-text');
    
    if (successDiv && successText) {
        successText.textContent = message;
        successDiv.style.display = 'block';
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 5000);
    } else {
        // Fallback to alert if elements not found
        alert('Success: ' + message);
    }
}

// ❌ Show error message
function showError(message) {
    hideMessages();
    const errorDiv = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    
    if (errorDiv && errorText) {
        errorText.textContent = message;
        errorDiv.style.display = 'block';
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 10000);
    } else {
        // Fallback to alert if elements not found
        alert('Error: ' + message);
    }
}

// 🙈 Hide all messages
function hideMessages() {
    const successDiv = document.getElementById('success-message');
    const errorDiv = document.getElementById('error-message');
    
    if (successDiv) successDiv.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'none';
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Journal line functions initialized');
    
    // Update initial state
    updateRemoveButtons();
    updateBalance();
    
    // 💰 Load cash locations for the current company
    loadCashLocations().then(() => {
        console.log('Cash locations ready for smart account detection');
    }).catch(error => {
        console.error('Failed to load cash locations:', error);
    });
});

// 🚀 Quick Action Functions - Cash, Bank, Expense, Revenue Templates
function addQuickLine(type) {
    console.log('Quick action triggered:', type);
    
    // Clear existing lines first
    clearAllJournalLines();
    
    // Define Quick Action templates
    const quickTemplates = {
        'cash': {
            description: 'Cash transaction',
            lines: [
                {
                    account_name: 'Cash',
                    line_description: 'Cash received',
                    debit: true,
                    amount: ''
                },
                {
                    account_name: 'Sales revenue',
                    line_description: 'Sales revenue',
                    debit: false,
                    amount: ''
                }
            ]
        },
        'bank': {
            description: 'Bank transaction',
            lines: [
                {
                    account_name: 'Bank Account',
                    line_description: 'Bank deposit',
                    debit: true,
                    amount: ''
                },
                {
                    account_name: 'Sales revenue',
                    line_description: 'Sales revenue',
                    debit: false,
                    amount: ''
                }
            ]
        },
        'expense': {
            description: 'Expense entry',
            lines: [
                {
                    account_name: 'Office Expenses',
                    line_description: 'Office expense',
                    debit: true,
                    amount: ''
                },
                {
                    account_name: 'Cash',
                    line_description: 'Cash payment',
                    debit: false,
                    amount: ''
                }
            ]
        },
        'revenue': {
            description: 'Revenue entry',
            lines: [
                {
                    account_name: 'Accounts Receivable',
                    line_description: 'Services provided',
                    debit: true,
                    amount: ''
                },
                {
                    account_name: 'Service Revenue',
                    line_description: 'Service revenue',
                    debit: false,
                    amount: ''
                }
            ]
        }
    };
    
    const template = quickTemplates[type];
    if (!template) {
        showError('Unknown quick action type: ' + type);
        return;
    }
    
    // Apply template to form
    applyQuickTemplate(template);
    
    // Show success message
    showSuccess(`${type.charAt(0).toUpperCase() + type.slice(1)} template applied successfully! Please enter amounts and review before saving.`);
}

// Apply Quick Template to form using searchable dropdown system
function applyQuickTemplate(template) {
    try {
        // Set description
        document.getElementById('description').value = template.description;
        
        // Apply each line using the new searchable dropdown system
        template.lines.forEach((lineData, index) => {
            // Get the account ID by name
            const accountId = findAccountIdByName(lineData.account_name);
            if (!accountId) {
                console.warn(`Account not found: ${lineData.account_name}`);
                return;
            }
            
            // Get the account name from our data
            const account = accountsData.find(acc => acc.id === accountId);
            if (!account) {
                console.warn(`Account data not found for ID: ${accountId}`);
                return;
            }
            
            // Get or create the line element
            let lineIndex = index + 1;
            let lineRow = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
            
            // If line doesn't exist, add a new one
            if (!lineRow) {
                addJournalLine();
                lineRow = document.querySelector(`tr[data-line-index="${lineCounter}"]`);
                lineIndex = lineCounter;
            }
            
            if (lineRow) {
                // Set account using new searchable dropdown system
                const accountInput = lineRow.querySelector('.account-search-input');
                const hiddenInput = lineRow.querySelector('.account-id-hidden');
                
                if (accountInput && hiddenInput) {
                    accountInput.value = account.name;
                    hiddenInput.value = account.id;
                }
                
                // Set line description
                const descInput = lineRow.querySelector('input[name="line_description[]"]');
                if (descInput) {
                    descInput.value = lineData.line_description;
                }
                
                // Focus on amount field for user input
                if (index === 0) {
                    const amountField = lineData.debit ? 
                        lineRow.querySelector('.debit-input') : 
                        lineRow.querySelector('.credit-input');
                    if (amountField) {
                        setTimeout(() => {
                            amountField.focus();
                            amountField.select();
                        }, 100);
                    }
                }
            }
        });
        
        // Update balance and validation
        updateBalance();
        validateForm();
        updateRemoveButtons();
        
    } catch (error) {
        console.error('Error applying quick template:', error);
        showError('Failed to apply template. Please try again.');
    }
}

// Find account ID by account name
function findAccountIdByName(accountName) {
    // Use accountsData array from the searchable dropdown
    if (!accountsData || accountsData.length === 0) return null;
    
    // Try exact name match first
    for (let account of accountsData) {
        if (account.name.trim() === accountName) {
            return account.id;
        }
    }
    
    // Try partial match if no exact match
    for (let account of accountsData) {
        if (account.name.toLowerCase().includes(accountName.toLowerCase())) {
            return account.id;
        }
    }
    
    return null;
}

// 🎭 Advanced Features - Type 컬럼 제거로 인해 단순화됨
// Cash 계정 감지는 이제 onAccountSelected 함수에서 자동으로 처리됨
// Location 드롭다운은 Cash 계정 선택 시 자동으로 표시됨

// 💰 Cash Location 드롭다운을 위한 기본 함수
function populateCashDropdown(selectElement, cashLocations) {
    console.log('Populating cash location dropdown with:', cashLocations?.length || 0, 'locations');
    
    // Clear existing options except the first one
    selectElement.innerHTML = '<option value="">Select Location</option>';
    
    if (cashLocations && cashLocations.length > 0) {
        cashLocations.forEach(location => {
            const option = document.createElement('option');
            option.value = location.cash_location_id;
            option.textContent = location.display_name || location.location_name;
            selectElement.appendChild(option);
            console.log('Added option:', location.display_name || location.location_name, '- ID:', location.cash_location_id);
        });
        console.log('Successfully populated', cashLocations.length, 'cash location options');
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No cash locations available';
        option.disabled = true;
        selectElement.appendChild(option);
        console.warn('No cash locations available to populate');
    }
}

// 💰 Cash Location 기능만 유지 (Type 컬럼 제거로 인해 단순화됨)
// 향후 필요시 Debt, Asset 관련 기능은 별도 구현 예정

// 💰 Account Selection Handler - Cash 계정 감지 및 Location 활성화
function onAccountSelected(lineElement, accountId, categoryTag) {
    console.log('onAccountSelected called:', accountId, categoryTag);
    
    const accountType = detectCashAccountType(accountId, categoryTag);
    
    if (accountType === 'cash') {
        console.log('Cash 계정 선택됨 - Location 활성화');
        showCashLocationColumn(lineElement);
        
        // ✨ 작업 5-1: Cash 계정 선택 시 사용자 친화적 안내 메시지 표시
        showCashAccountGuidance(lineElement);
        
        // ✨ 작업 5-1: Location 드롭다운에 자동 포커스 및 툴팁 추가
        focusLocationDropdown(lineElement);
        
    } else {
        console.log('일반 계정 선택됨 - Location 비활성화');
        hideCashLocationColumn(lineElement);
        
        // Cash 관련 안내 메시지 제거
        removeCashAccountGuidance(lineElement);
    }
}

// 💰 Show Cash Location Column for Cash Account (Enhanced with animations)
function showCashLocationColumn(lineElement) {
    // 기존 Location 셀들을 먼저 확인
    const existingLocationCells = lineElement.querySelectorAll('.location-selector-container');
    
    if (existingLocationCells.length > 0) {
        // 이미 Location 셀들이 있다면 첫 번째만 표시하고 나머지는 숨김
        existingLocationCells.forEach((cell, index) => {
            if (index === 0) {
                // ✨ 부드러운 애니메이션으로 표시
                animateLocationColumn(lineElement, true);
                
                // Cash 위치 데이터 로드
                const locationSelect = cell.querySelector('.cash-location-select');
                if (locationSelect) {
                    populateCashDropdown(locationSelect, window.cashLocationsData || []);
                    
                    // ✨ 필드 하이라이트 효과
                    setTimeout(() => {
                        highlightLocationField(lineElement, true);
                        
                        // ✨ 하이라이트를 일정 시간 후 제거
                        setTimeout(() => {
                            highlightLocationField(lineElement, false);
                        }, 4000);
                    }, 500);
                    
                    // ✨ Location 선택 이벤트 리스너 추가
                    locationSelect.addEventListener('change', function() {
                        if (this.value) {
                            showLocationSuccess(lineElement);
                        } else {
                            showLocationWarning(lineElement, true);
                        }
                    });
                }
            } else {
                cell.style.display = 'none'; // 중복 Location 셀들 숨김
            }
        });
        console.log('Existing cash location columns found and configured with animations');
        return;
    }
    
    // Location 셀이 없다면 새로 생성
    const locationCell = document.createElement('td');
    locationCell.className = 'location-selector-container';
    locationCell.innerHTML = `
        <select class="form-select form-select-sm cash-location-select" name="cash_location_id[]">
            <option value="">Select Location</option>
        </select>
    `;
    
    // Credit 열 다음에 삽입 (올바른 name 속성 사용)
    const creditInput = lineElement.querySelector('input[name="credit_amount[]"]');
    if (creditInput) {
        const creditCell = creditInput.closest('td');
        creditCell.parentNode.insertBefore(locationCell, creditCell.nextSibling);
        
        // Cash 위치 데이터 로드
        const locationSelect = locationCell.querySelector('.cash-location-select');
        populateCashDropdown(locationSelect, window.cashLocationsData || []);
        
        // ✨ 부드러운 애니메이션으로 표시
        animateLocationColumn(lineElement, true);
        
        // ✨ 필드 하이라이트 효과
        setTimeout(() => {
            highlightLocationField(lineElement, true);
            
            // ✨ 하이라이트를 일정 시간 후 제거
            setTimeout(() => {
                highlightLocationField(lineElement, false);
            }, 4000);
        }, 500);
        
        // ✨ Location 선택 이벤트 리스너 추가
        locationSelect.addEventListener('change', function() {
            if (this.value) {
                showLocationSuccess(lineElement);
            } else {
                showLocationWarning(lineElement, true);
            }
        });
        
        console.log('New cash location column created and inserted with animations');
    } else {
        console.error('Credit input not found for cash location insertion');
    }
}

// 🚫 Hide Cash Location Column for Non-Cash Account (Enhanced with animations)
function hideCashLocationColumn(lineElement) {
    const locationCells = lineElement.querySelectorAll('.location-selector-container');
    
    if (locationCells.length > 0) {
        locationCells.forEach(cell => {
            // ✨ 부드러운 애니메이션으로 숨김
            animateLocationColumn(lineElement, false);
            
            // 선택된 값 초기화
            const locationSelect = cell.querySelector('.cash-location-select');
            if (locationSelect) {
                locationSelect.value = '';
                
                // ✨ 모든 스타일 및 경고 제거
                highlightLocationField(lineElement, false);
                showLocationWarning(lineElement, false);
            }
        });
        console.log('Cash location columns hidden with animation for line');
    } else {
        console.log('No location columns found to hide');
    }
}

console.log('Journal Line Functions loaded successfully! (Phase 5.1 Enhanced Cash Modal implemented)');

// Initialize store change listener when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeStoreChangeListener();
});
// ✨ 작업 5-1: Cash 계정 선택 시 사용자 친화적 안내 메시지 표시
function showCashAccountGuidance(lineElement) {
    // 기존 안내 메시지가 있으면 제거
    removeCashAccountGuidance(lineElement);
    
    // 안내 메시지 생성
    const guidanceDiv = document.createElement('div');
    guidanceDiv.className = 'cash-guidance-message alert alert-info alert-sm mt-2';
    guidanceDiv.innerHTML = `
        <i class="fas fa-info-circle me-2"></i>
        
    `;
    guidanceDiv.style.cssText = `
        font-size: 0.85em;
        padding: 8px 12px;
        margin-top: 8px;
        border-left: 4px solid #0dcaf0;
        background-color: #e7f3ff;
        border-radius: 6px;
        animation: fadeInSlide 0.3s ease-in-out;
    `;
    
    // Account 셀 아래에 안내 메시지 추가
    const accountCell = lineElement.querySelector('.account-search-input')?.closest('td');
    if (accountCell) {
        accountCell.appendChild(guidanceDiv);
        console.log('Cash account guidance message displayed');
    }
}

// ✨ 작업 5-1: Cash 관련 안내 메시지 제거
function removeCashAccountGuidance(lineElement) {
    const guidanceMessage = lineElement.querySelector('.cash-guidance-message');
    if (guidanceMessage) {
        guidanceMessage.style.animation = 'fadeOutSlide 0.2s ease-in-out';
        setTimeout(() => {
            guidanceMessage.remove();
        }, 200);
        console.log('Cash account guidance message removed');
    }
}

// ✨ 작업 5-1: Location 드롭다운에 자동 포커스 및 툴팁 추가
function focusLocationDropdown(lineElement) {
    setTimeout(() => {
        const locationSelect = lineElement.querySelector('.cash-location-select');
        if (locationSelect) {
            // 자동 포커스
            locationSelect.focus();
            
            // 하이라이트 효과 추가
            locationSelect.style.cssText += `
                border: 2px solid #0dcaf0;
                box-shadow: 0 0 8px rgba(13, 202, 240, 0.3);
                transition: all 0.3s ease;
            `;
            
            // 툴팁 추가
            locationSelect.title = "Cash transactions require location information. Please select the location where cash is stored.";
            
            console.log('Location dropdown focused and highlighted');
            
            // 몇 초 후 하이라이트 제거
            setTimeout(() => {
                locationSelect.style.border = '';
                locationSelect.style.boxShadow = '';
            }, 3000);
        }
    }, 100);
}

// ✨ 작업 5-3: Location 드롭다운 부드러운 애니메이션 효과
function animateLocationColumn(lineElement, show = true) {
    const locationCell = lineElement.querySelector('.location-selector-container');
    if (!locationCell) return;
    
    if (show) {
        // Show animation
        locationCell.style.cssText += `
            opacity: 0;
            transform: translateX(-20px);
            display: table-cell;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        
        setTimeout(() => {
            locationCell.style.opacity = '1';
            locationCell.style.transform = 'translateX(0)';
        }, 10);
    } else {
        // Hide animation
        locationCell.style.cssText += `
            opacity: 1;
            transform: translateX(0);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        
        setTimeout(() => {
            locationCell.style.opacity = '0';
            locationCell.style.transform = 'translateX(-20px)';
            
            setTimeout(() => {
                locationCell.style.display = 'none';
            }, 300);
        }, 10);
    }
}

// ✨ 작업 5-3: Cash 계정 선택 시 Location 필드 강조 하이라이트
function highlightLocationField(lineElement, highlight = true) {
    const locationSelect = lineElement.querySelector('.cash-location-select');
    if (!locationSelect) return;
    
    if (highlight) {
        locationSelect.classList.add('location-required-highlight');
        locationSelect.style.cssText += `
            border: 2px solid #ffc107 !important;
            background-color: #fff8e1;
            box-shadow: 0 0 12px rgba(255, 193, 7, 0.4);
            animation: pulse-glow 2s infinite;
        `;
    } else {
        locationSelect.classList.remove('location-required-highlight');
        locationSelect.style.border = '';
        locationSelect.style.backgroundColor = '';
        locationSelect.style.boxShadow = '';
        locationSelect.style.animation = '';
    }
}

// ✨ 작업 5-3: Location 미선택 시 시각적 경고 표시
function showLocationWarning(lineElement, show = true) {
    const locationCell = lineElement.querySelector('.location-selector-container');
    if (!locationCell) return;
    
    // Remove existing warning
    const existingWarning = locationCell.querySelector('.location-warning');
    if (existingWarning) {
        existingWarning.remove();
    }
    
    if (show) {
        const warningDiv = document.createElement('div');
        warningDiv.className = 'location-warning';
        warningDiv.innerHTML = `
            <small class="text-danger">
                <i class="fas fa-exclamation-triangle me-1"></i>
                s
            </small>
        `;
        warningDiv.style.cssText = `
            margin-top: 4px;
            padding: 4px 8px;
            background-color: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 4px;
            animation: shake 0.5s ease-in-out;
        `;
        
        locationCell.appendChild(warningDiv);
        
        // Add error styling to select
        const locationSelect = locationCell.querySelector('.cash-location-select');
        if (locationSelect) {
            locationSelect.style.cssText += `
                border-color: #dc3545 !important;
                background-color: #fff5f5;
            `;
        }
    }
}

// ✨ 작업 5-3: Location 선택 완료 시 성공 피드백
function showLocationSuccess(lineElement) {
    const locationSelect = lineElement.querySelector('.cash-location-select');
    if (!locationSelect || !locationSelect.value) return;
    
    // Remove any warnings
    showLocationWarning(lineElement, false);
    highlightLocationField(lineElement, false);
    
    // Show success styling
    locationSelect.style.cssText += `
        border-color: #28a745 !important;
        background-color: #f8fff9;
        transition: all 0.3s ease;
    `;
    
    // Add success checkmark
    const locationCell = locationSelect.closest('.location-selector-container');
    if (locationCell && !locationCell.querySelector('.location-success')) {
        const successIcon = document.createElement('span');
        successIcon.className = 'location-success';
        successIcon.innerHTML = `
            <i class="fas fa-check-circle text-success ms-2"></i>
        `;
        successIcon.style.cssText = `
            animation: fadeIn 0.3s ease-in-out;
        `;
        
        locationCell.appendChild(successIcon);
        
        // Remove success styling after a few seconds
        setTimeout(() => {
            locationSelect.style.borderColor = '';
            locationSelect.style.backgroundColor = '';
            if (successIcon.parentNode) {
                successIcon.remove();
            }
        }, 3000);
    }
}

// 🏪 Handle store selection change - refresh cash locations for all cash accounts
async function handleStoreChange() {
    console.log('Store selection changed - refreshing cash locations');
    
    // Find all lines with cash accounts selected
    const allLines = document.querySelectorAll('.journal-line');
    
    for (const line of allLines) {
        const accountIdInput = line.querySelector('.account-id-hidden');
        const accountId = accountIdInput ? accountIdInput.value : null;
        
        if (accountId) {
            // Check if this is a cash account
            const accountType = detectCashAccountType(accountId, 'cash'); // We assume cash category for this check
            
            if (accountType === 'cash') {
                // Refresh the cash location selector for this line
                await showCashLocationSelector(line, 'cash');
                console.log('Refreshed cash location for line:', line.querySelector('.line-number')?.textContent);
            }
        }
    }
}

// 🎬 Initialize store change listener
function initializeStoreChangeListener() {
    const storeSelect = document.querySelector('[name="store_id"]');
    if (storeSelect) {
        storeSelect.addEventListener('change', handleStoreChange);
        console.log('Store change listener initialized');
    } else {
        console.warn('Store select element not found');
    }
}
