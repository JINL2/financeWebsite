/**
 * Phase 5.6: Real-time Data Validation and Suggestions - FIXED VERSION
 */

// Global variables for validation
let validationRules = {};
let userPatterns = {};
let anomalyDetection = {};

// Initialize real-time validation system
function initializeRealTimeValidation() {
    console.log('Initializing real-time validation system...');
    
    // Load validation rules
    loadValidationRules();
    
    // Load user patterns
    loadUserPatterns();
    
    // Initialize anomaly detection
    initializeAnomalyDetection();
    
    // Set up event listeners for real-time validation
    setupValidationEventListeners();
    
    console.log('Real-time validation system initialized successfully');
}

// Load validation rules from server or use defaults
function loadValidationRules() {
    // Try to load from server
    fetch('validation_rules.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                validationRules = data.rules;
            } else {
                validationRules = getDefaultValidationRules();
            }
        })
        .catch(error => {
            console.warn('Could not load validation rules, using defaults:', error);
            validationRules = getDefaultValidationRules();
        });
}

// Get default validation rules
function getDefaultValidationRules() {
    return {
        amounts: {
            maxSingleAmount: 100000000, // 100M VND
            minAmount: 0.01,
            warningThreshold: 10000000, // 10M VND
            requireConfirmation: 50000000 // 50M VND
        },
        accounts: {
            forbiddenCombinations: [],
            requireDescription: ['expense', 'revenue'],
            warningCombinations: []
        },
        timePatterns: {
            allowFutureDates: false,
            maxDaysBack: 90,
            weekendWarning: true,
            holidayWarning: true
        },
        descriptions: {
            minLength: 3,
            maxLength: 500,
            requiredWords: [],
            forbiddenWords: []
        }
    };
}

// Load user patterns for intelligent suggestions
function loadUserPatterns() {
    const user_id = new URLSearchParams(window.location.search).get('user_id');
    const company_id = new URLSearchParams(window.location.search).get('company_id');
    
    if (!user_id || !company_id) {
        console.warn('User ID or Company ID not found, skipping pattern loading');
        return;
    }
    
    fetch(`user_patterns.php?user_id=${user_id}&company_id=${company_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                userPatterns = data.patterns;
                console.log('User patterns loaded successfully');
            }
        })
        .catch(error => {
            console.warn('Could not load user patterns:', error);
        });
}

// Set up event listeners for real-time validation
function setupValidationEventListeners() {
    // Amount field validation
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('debit-input') || e.target.classList.contains('credit-input')) {
            validateAmount(e.target);
        }
    });
    
    // Account selection validation
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('account-select') || e.target.classList.contains('account-search-input')) {
            validateAccountCombination();
        }
    });
    
    // Description validation
    document.addEventListener('input', function(e) {
        if (e.target.name === 'line_description[]') {
            validateDescription(e.target);
        }
    });
    
    // Date validation
    document.addEventListener('change', function(e) {
        if (e.target.id === 'entry_date') {
            validateTimePattern(e.target.value);
        }
    });
    
    // Store validation
    document.addEventListener('change', function(e) {
        if (e.target.id === 'store_id') {
            validateStorePattern();
        }
    });
}

// Validate amount inputs
function validateAmount(input) {
    const amount = parseFloat(input.value) || 0;
    const rules = validationRules.amounts || getDefaultValidationRules().amounts;
    const container = input.closest('td');
    
    // Clear previous messages
    clearValidationMessage(container, 'amount');
    
    if (amount === 0) return;
    
    // Check minimum amount
    if (amount < rules.minAmount) {
        showValidationMessage(container, 'amount', {
            type: 'error',
            title: 'Amount Too Small',
            message: `Minimum amount is ${formatCurrency(rules.minAmount)}`,
            suggestions: ['Enter a valid amount', 'Use correct decimal places']
        });
        return;
    }
    
    // Check maximum amount
    if (amount > rules.maxSingleAmount) {
        showValidationMessage(container, 'amount', {
            type: 'error',
            title: 'Amount Too Large',
            message: `Maximum single amount is ${formatCurrency(rules.maxSingleAmount)}`,
            suggestions: ['Split into multiple entries', 'Verify the amount is correct']
        });
        return;
    }
    
    // Warning for large amounts
    if (amount > rules.warningThreshold) {
        showValidationMessage(container, 'amount', {
            type: 'warning',
            title: 'Large Amount Warning',
            message: `This is a large amount: ${formatCurrency(amount)}`,
            suggestions: ['Double-check the amount', 'Ensure proper authorization', 'Consider supporting documentation']
        });
    }
    
    // Check against user patterns
    if (userPatterns.amounts) {
        const unusualAmount = detectUnusualAmount(amount);
        if (unusualAmount) {
            showValidationMessage(container, 'amount-pattern', {
                type: 'info',
                title: 'Unusual Amount Pattern',
                message: unusualAmount.message,
                suggestions: unusualAmount.suggestions
            });
        }
    }
}

// Detect unusual amounts based on user patterns
function detectUnusualAmount(amount) {
    if (!userPatterns.amounts || userPatterns.amounts.length < 5) return null;
    
    const amounts = userPatterns.amounts;
    const mean = amounts.reduce((sum, a) => sum + a, 0) / amounts.length;
    const variance = amounts.reduce((sum, a) => sum + Math.pow(a - mean, 2), 0) / amounts.length;
    const stdDev = Math.sqrt(variance);
    
    // Z-score calculation
    const zScore = Math.abs((amount - mean) / stdDev);
    
    if (zScore > 3) {
        return {
            message: `This amount is significantly different from your usual pattern (avg: ${formatCurrency(mean)})`,
            suggestions: [
                'Verify this amount is correct',
                'Consider if this should be multiple entries',
                'Check for decimal point errors'
            ]
        };
    }
    
    return null;
}

// Validate account combinations
function validateAccountCombination() {
    const accountSelects = document.querySelectorAll('.account-search-input');
    const selectedAccounts = [];
    
    accountSelects.forEach(select => {
        const hiddenInput = select.closest('.account-dropdown-container').querySelector('.account-id-hidden');
        if (hiddenInput && hiddenInput.value) {
            selectedAccounts.push(hiddenInput.value);
        }
    });
    
    if (selectedAccounts.length < 2) return;
    
    const rules = validationRules.accounts || getDefaultValidationRules().accounts;
    
    // Clear previous messages
    clearGlobalValidationMessage('account-combination');
    
    // Check forbidden combinations
    const hasForbidden = checkForbiddenCombinations(selectedAccounts, rules.forbiddenCombinations);
    if (hasForbidden) {
        showGlobalValidationMessage({
            type: 'error',
            title: 'Forbidden Account Combination',
            message: hasForbidden.message,
            suggestions: hasForbidden.suggestions
        }, 'account-combination');
        return;
    }
    
    // Check warning combinations
    const hasWarning = checkWarningCombinations(selectedAccounts, rules.warningCombinations);
    if (hasWarning) {
        showGlobalValidationMessage({
            type: 'warning',
            title: 'Unusual Account Combination',
            message: hasWarning.message,
            suggestions: hasWarning.suggestions
        }, 'account-combination');
    }
    
    // Check against user patterns
    if (userPatterns.accountCombinations) {
        const unusualCombination = detectUnusualAccountCombination(selectedAccounts);
        if (unusualCombination) {
            showGlobalValidationMessage({
                type: 'info',
                title: 'New Account Combination',
                message: unusualCombination.message,
                suggestions: unusualCombination.suggestions
            }, 'account-pattern');
        }
    }
}

// Check for forbidden account combinations
function checkForbiddenCombinations(selectedAccounts, forbiddenCombinations) {
    for (const forbidden of forbiddenCombinations) {
        if (forbidden.accounts.every(acc => selectedAccounts.includes(acc))) {
            return {
                message: forbidden.message || 'This account combination is not allowed',
                suggestions: forbidden.suggestions || ['Select different accounts', 'Review transaction purpose']
            };
        }
    }
    return null;
}

// Check for warning account combinations
function checkWarningCombinations(selectedAccounts, warningCombinations) {
    for (const warning of warningCombinations) {
        if (warning.accounts.every(acc => selectedAccounts.includes(acc))) {
            return {
                message: warning.message || 'This account combination requires attention',
                suggestions: warning.suggestions || ['Review transaction details', 'Ensure proper documentation']
            };
        }
    }
    return null;
}

// Detect unusual account combinations
function detectUnusualAccountCombination(selectedAccounts) {
    if (!userPatterns.accountCombinations) return null;
    
    const combinations = userPatterns.accountCombinations;
    const currentCombo = selectedAccounts.sort().join('-');
    
    // Check if this combination has been used before
    const usageCount = combinations[currentCombo] || 0;
    
    if (usageCount === 0) {
        return {
            message: 'You haven\'t used this account combination before',
            suggestions: [
                'Verify this is the correct transaction type',
                'Review chart of accounts',
                'Consider similar transactions you\'ve made'
            ]
        };
    }
    
    return null;
}

// Validate description fields
function validateDescription(input) {
    const description = input.value.trim();
    const rules = validationRules.descriptions || getDefaultValidationRules().descriptions;
    const container = input.closest('td');
    
    // Clear previous messages
    clearValidationMessage(container, 'description');
    
    if (description.length === 0) return;
    
    // Check minimum length
    if (description.length < rules.minLength) {
        showValidationMessage(container, 'description', {
            type: 'warning',
            title: 'Description Too Short',
            message: `Description should be at least ${rules.minLength} characters`,
            suggestions: ['Provide more detail', 'Include transaction purpose']
        });
        return;
    }
    
    // Check maximum length
    if (description.length > rules.maxLength) {
        showValidationMessage(container, 'description', {
            type: 'error',
            title: 'Description Too Long',
            message: `Description exceeds ${rules.maxLength} characters`,
            suggestions: ['Shorten the description', 'Use abbreviations where appropriate']
        });
        return;
    }
    
    // Check for forbidden words
    const hasForbiddenWords = checkForbiddenWords(description, rules.forbiddenWords);
    if (hasForbiddenWords) {
        showValidationMessage(container, 'description', {
            type: 'warning',
            title: 'Inappropriate Content',
            message: 'Description contains words that should be avoided',
            suggestions: ['Use professional language', 'Replace inappropriate terms']
        });
    }
    
    // Provide intelligent suggestions
    if (description.length >= 3) {
        suggestDescriptionCompletions(input, description);
    }
}

// Check for forbidden words in description
function checkForbiddenWords(description, forbiddenWords) {
    const lowerDesc = description.toLowerCase();
    return forbiddenWords.some(word => lowerDesc.includes(word.toLowerCase()));
}

// Suggest description completions
function suggestDescriptionCompletions(input, description) {
    // Get account context
    const row = input.closest('tr');
    const accountInput = row.querySelector('.account-search-input');
    const hiddenAccountInput = row.querySelector('.account-id-hidden');
    const accountId = hiddenAccountInput ? hiddenAccountInput.value : null;
    
    if (!accountId) return;
    
    // Fetch suggestions from server
    const user_id = new URLSearchParams(window.location.search).get('user_id');
    const company_id = new URLSearchParams(window.location.search).get('company_id');
    
    fetch(`autocomplete.php?action=description_suggestions&user_id=${user_id}&company_id=${company_id}&account_id=${accountId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.suggestions && data.suggestions.length > 0) {
                // Filter suggestions based on current input
                const filtered = data.suggestions.filter(suggestion => 
                    suggestion.toLowerCase().includes(description.toLowerCase()) ||
                    description.toLowerCase().includes(suggestion.toLowerCase())
                );
                
                if (filtered.length > 0) {
                    showDescriptionSuggestions(input, filtered);
                }
            }
        })
        .catch(error => {
            console.warn('Could not fetch description suggestions:', error);
        });
}

// Show description suggestions dropdown
function showDescriptionSuggestions(input, suggestions) {
    // Remove existing suggestions
    const existingDropdown = document.querySelector('.description-suggestions');
    if (existingDropdown) {
        existingDropdown.remove();
    }
    
    // Create suggestions dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'description-suggestions position-absolute bg-white border rounded shadow-sm';
    dropdown.style.cssText = `
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
    `;
    
    // Add suggestions to dropdown
    suggestions.forEach((suggestion, index) => {
        const item = document.createElement('div');
        item.className = 'p-2 cursor-pointer hover:bg-light';
        item.textContent = suggestion;
        item.style.cssText = 'cursor: pointer; padding: 0.5rem; border-bottom: 1px solid #f8f9fa;';
        
        item.addEventListener('mouseenter', () => {
            item.style.backgroundColor = '#f8f9fa';
        });
        
        item.addEventListener('mouseleave', () => {
            item.style.backgroundColor = '';
        });
        
        item.addEventListener('click', () => {
            input.value = suggestion;
            dropdown.remove();
            
            // Trigger validation
            validateDescription(input);
            
            // Focus next field
            const nextField = findNextInputField(input);
            if (nextField) {
                nextField.focus();
            }
        });
        
        dropdown.appendChild(item);
    });
    
    // Position dropdown relative to input
    const inputContainer = input.closest('td') || input.closest('.form-group');
    inputContainer.style.position = 'relative';
    inputContainer.appendChild(dropdown);
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        if (dropdown.parentNode) {
            dropdown.remove();
        }
    }, 10000);
    
    // Hide on click outside
    document.addEventListener('click', function hideDropdown(e) {
        if (!dropdown.contains(e.target) && e.target !== input) {
            dropdown.remove();
            document.removeEventListener('click', hideDropdown);
        }
    });
}

// Find next input field for smart navigation
function findNextInputField(currentInput) {
    const row = currentInput.closest('tr');
    if (!row) return null;
    
    // Check for amount fields in same row
    const debitInput = row.querySelector('.debit-input');
    const creditInput = row.querySelector('.credit-input');
    
    if (currentInput.classList.contains('line-description')) {
        return debitInput && !debitInput.value ? debitInput : creditInput;
    }
    
    // Move to next row if current row is complete
    const nextRow = row.nextElementSibling;
    if (nextRow && nextRow.classList.contains('journal-line')) {
        return nextRow.querySelector('.account-select');
    }
    
    return null;
}

// Validate time patterns
function validateTimePattern(entryDate) {
    const date = new Date(entryDate);
    const now = new Date();
    const rules = validationRules.timePatterns || getDefaultValidationRules().timePatterns;
    
    clearGlobalValidationMessage('time-pattern');
    
    // Check for future dates
    if (date > now) {
        showGlobalValidationMessage({
            type: 'warning',
            title: 'Future Date Warning',
            message: 'Entry date is in the future. Is this intentional?',
            suggestions: ['Verify the date is correct', 'Use today\'s date for current transactions']
        }, 'time-pattern');
    }
    
    // Check for weekend entries (if enabled)
    if (rules.weekendWarning && (date.getDay() === 0 || date.getDay() === 6)) {
        showGlobalValidationMessage({
            type: 'info',
            title: 'Weekend Entry',
            message: 'This entry is dated on a weekend.',
            suggestions: ['Verify this is a weekend transaction', 'Consider using next business day']
        }, 'time-pattern');
    }
    
    // Check for very old dates
    const daysDiff = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    if (daysDiff > 30) {
        showGlobalValidationMessage({
            type: 'warning',
            title: 'Old Date Warning',
            message: `Entry date is ${daysDiff} days ago. Is this a backdated entry?`,
            suggestions: ['Verify the date is correct', 'Add explanation for backdated entries']
        }, 'time-pattern');
    }
}

// Validate store patterns
function validateStorePattern() {
    const storeSelect = document.getElementById('store_id');
    const storeId = storeSelect.value;
    
    if (!storeId || !userPatterns.stores) return;
    
    clearGlobalValidationMessage('store-pattern');
    
    // Get store usage statistics
    const storeStats = userPatterns.stores[storeId];
    if (storeStats) {
        // Check if this is an unusual time for this store
        const currentHour = new Date().getHours();
        const normalHours = storeStats.normalHours || [9, 17];
        
        if (currentHour < normalHours[0] || currentHour > normalHours[1]) {
            showGlobalValidationMessage({
                type: 'info',
                title: 'Unusual Time for Store',
                message: `Entries for this store are typically made between ${normalHours[0]}:00 - ${normalHours[1]}:00.`,
                suggestions: ['Verify this is the correct store', 'Consider if this is an after-hours transaction']
            }, 'store-pattern');
        }
        
        // Suggest common accounts for this store
        if (storeStats.commonAccounts && storeStats.commonAccounts.length > 0) {
            setTimeout(() => {
                suggestStoreAccounts(storeStats.commonAccounts);
            }, 500);
        }
    }
}

// Initialize anomaly detection
function initializeAnomalyDetection() {
    anomalyDetection = {
        amountThresholds: {
            low: 0,
            high: 0,
            count: 0
        },
        timePatterns: {},
        accountUsage: {},
        lastValidation: Date.now()
    };
    
    console.log('Anomaly detection initialized');
}

// Validation message management
function showValidationMessage(container, messageId, message) {
    clearValidationMessage(container, messageId);
    
    const messageElement = document.createElement('div');
    messageElement.className = `validation-message validation-${messageId} mt-2`;
    messageElement.innerHTML = createValidationMessageHTML(message);
    
    container.appendChild(messageElement);
    
    // Auto-hide info messages after 10 seconds
    if (message.type === 'info') {
        setTimeout(() => {
            if (messageElement.parentNode) {
                messageElement.remove();
            }
        }, 10000);
    }
}

function showGlobalValidationMessage(message, messageId = 'global') {
    clearGlobalValidationMessage(messageId);
    
    const container = document.querySelector('.validation-messages-container') || createValidationContainer();
    
    const messageElement = document.createElement('div');
    messageElement.className = `validation-message global-validation-${messageId} alert alert-${getAlertClass(message.type)} alert-dismissible fade show`;
    messageElement.innerHTML = createGlobalValidationMessageHTML(message);
    
    container.appendChild(messageElement);
    
    // Auto-hide info messages
    if (message.type === 'info') {
        setTimeout(() => {
            if (messageElement.parentNode) {
                messageElement.remove();
            }
        }, 15000);
    }
}

function createValidationContainer() {
    const container = document.createElement('div');
    container.className = 'validation-messages-container';
    
    // Insert after the entry information card
    const entryCard = document.querySelector('.base-card');
    if (entryCard) {
        entryCard.insertAdjacentElement('afterend', container);
    }
    
    return container;
}

function createValidationMessageHTML(message) {
    let html = `
        <div class="d-flex align-items-start">
            <i class="bi bi-${getIconClass(message.type)} me-2 mt-1"></i>
            <div class="flex-grow-1">
                <strong>${message.title}</strong><br>
                <small>${message.message}</small>
    `;
    
    if (message.suggestions && message.suggestions.length > 0) {
        html += '<ul class="mt-1 mb-0 small">';
        message.suggestions.forEach(suggestion => {
            html += `<li>${suggestion}</li>`;
        });
        html += '</ul>';
    }
    
    html += '</div></div>';
    
    return html;
}

function createGlobalValidationMessageHTML(message) {
    let html = `
        <div class="d-flex align-items-start">
            <i class="bi bi-${getIconClass(message.type)} me-2 mt-1"></i>
            <div class="flex-grow-1">
                <strong>${message.title}</strong><br>
                ${message.message}
    `;
    
    if (message.suggestions && message.suggestions.length > 0) {
        html += '<ul class="mt-2 mb-0">';
        message.suggestions.forEach(suggestion => {
            html += `<li class="small">${suggestion}</li>`;
        });
        html += '</ul>';
    }
    
    if (message.actions && message.actions.length > 0) {
        html += '<div class="mt-2">';
        message.actions.forEach(action => {
            html += `<button class="btn btn-sm btn-outline-primary me-2" onclick="${action.action.toString().replace('function()', '')}">${action.text}</button>`;
        });
        html += '</div>';
    }
    
    html += `
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    return html;
}

function getIconClass(type) {
    switch (type) {
        case 'error': return 'exclamation-triangle-fill';
        case 'warning': return 'exclamation-triangle';
        case 'info': return 'info-circle';
        default: return 'info-circle';
    }
}

function getAlertClass(type) {
    switch (type) {
        case 'error': return 'danger';
        case 'warning': return 'warning';
        case 'info': return 'info';
        default: return 'info';
    }
}

function clearValidationMessage(container, messageId) {
    const existing = container.querySelector(`.validation-${messageId}`);
    if (existing) {
        existing.remove();
    }
}

function clearGlobalValidationMessage(messageId) {
    const existing = document.querySelector(`.global-validation-${messageId}`);
    if (existing) {
        existing.remove();
    }
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Export validation functions for external use
window.PhaseValidation = {
    initializeRealTimeValidation,
    validateAmount,
    validateAccountCombination,
    validateDescription,
    validateTimePattern,
    showValidationMessage,
    showGlobalValidationMessage,
    clearGlobalValidationMessage
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRealTimeValidation);
} else {
    initializeRealTimeValidation();
}
