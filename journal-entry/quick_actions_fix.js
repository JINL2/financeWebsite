// ========================================
// Quick Actions Fix - Missing Functions
// ========================================

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

// ✅ Validate form
function validateForm() {
    const lines = document.querySelectorAll('.journal-line');
    const saveBtn = document.getElementById('save-btn');
    
    let hasValidLines = 0;
    let isBalanced = true;
    let totalDebit = 0;
    let totalCredit = 0;
    
    // Check each line
    lines.forEach(line => {
        const hiddenInput = line.querySelector('.account-id-hidden');
        const debitInput = line.querySelector('.debit-input');
        const creditInput = line.querySelector('.credit-input');
        
        const hasAccount = hiddenInput && hiddenInput.value !== '';
        const debitAmount = parseFloat(debitInput.value) || 0;
        const creditAmount = parseFloat(creditInput.value) || 0;
        const hasAmount = debitAmount > 0 || creditAmount > 0;
        
        if (hasAccount && hasAmount) {
            hasValidLines++;
            totalDebit += debitAmount;
            totalCredit += creditAmount;
        }
    });
    
    // Check if balanced
    isBalanced = Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;
    
    // Enable/disable save button
    const isFormValid = hasValidLines >= 2 && isBalanced;
    
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
    
    return isFormValid;
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

// 🔧 Update remove buttons state
function updateRemoveButtons() {
    const removeButtons = document.querySelectorAll('.remove-line-btn');
    const lineCount = document.querySelectorAll('.journal-line').length;
    
    removeButtons.forEach(btn => {
        btn.disabled = lineCount <= 2;
    });
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
    
    // Clear all lines and reset
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

// 📄 Basic info validation for templates
function validateBasicInfo() {
    const storeId = document.getElementById('store_id').value;
    const entryDate = document.getElementById('entry_date').value;
    
    return storeId !== '' && entryDate !== '';
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
        
        const accountId = hiddenInput ? hiddenInput.value : '';
        const debitAmount = parseFloat(debitInput.value) || 0;
        const creditAmount = parseFloat(creditInput.value) || 0;
        const description = descInput ? descInput.value : '';
        
        if (accountId && (debitAmount > 0 || creditAmount > 0)) {
            lines.push({
                account_id: accountId,
                description: description,
                debit_amount: debitAmount,
                credit_amount: creditAmount
            });
        }
    });
    
    return lines;
}

// 📋 Collect form data for templates
function collectFormData() {
    const storeSelect = document.getElementById('store_id');
    const selectedStoreOption = storeSelect.options[storeSelect.selectedIndex];
    
    return {
        store_id: storeSelect.value,
        store_name: selectedStoreOption ? selectedStoreOption.text : '',
        description: document.getElementById('description').value,
        journal_lines: getJournalLines()
    };
}

// 🏃‍♂️ Add auto-save listeners to journal lines
function addJournalLineAutoSaveListeners() {
    // This can be implemented later for auto-save functionality
    console.log('Auto-save listeners added');
}

// 🎯 Apply Quick Template to searchable dropdown (NEW VERSION)
function applyQuickTemplateToSearchableDropdown(template) {
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
                // Set account using searchable dropdown
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
        
        // Add auto-save listeners to new lines
        addJournalLineAutoSaveListeners();
        
    } catch (error) {
        console.error('Error applying quick template:', error);
        showError('Failed to apply template. Please try again.');
    }
}

// 🔧 Override the original applyQuickTemplate to use searchable dropdown version
function applyQuickTemplate(template) {
    applyQuickTemplateToSearchableDropdown(template);
}

console.log('Quick Actions Fix loaded successfully!');
