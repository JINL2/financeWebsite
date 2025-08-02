        // Apply Quick Template to form using new searchable dropdown system
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
                
                // Add auto-save listeners to new lines
                addJournalLineAutoSaveListeners();
                
            } catch (error) {
                console.error('Error applying quick template:', error);
                showError('Failed to apply template. Please try again.');
            }
        }