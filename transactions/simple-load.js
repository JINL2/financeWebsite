// Simple transaction loader for testing
console.log('Simple transaction loader starting...');

// Simple function to load transactions
function loadTransactionsNow() {
    console.log('Loading transactions now...');
    
    const apiUrl = 'api.php?action=get_transactions&user_id=0d2e61ad-e230-454e-8b90-efbe1c1a9268&company_id=ebd66ba7-fde7-4332-b6b5-0d8a7f615497&limit=10';
    
    fetch(apiUrl)
        .then(response => response.json())
        .then(data => {
            console.log('API response:', data);
            
            if (data.success) {
                const tbody = document.querySelector('#transactions-table tbody');
                if (tbody) {
                    // Clear loading message
                    tbody.innerHTML = '';
                    
                    // Add transactions
                    data.data.forEach(entry => {
                        const debitAmount = entry.total_debit > 0 ? `₫${entry.total_debit.toLocaleString()}` : '-';
                        const creditAmount = entry.total_credit > 0 ? `₫${entry.total_credit.toLocaleString()}` : '-';
                        const storeName = entry.lines[0]?.store_name || 'N/A';
                        const description = entry.description || 'No description';
                        
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>
                                <div class="fw-bold">${entry.entry_date}</div>
                                <div class="text-muted small">${description}</div>
                            </td>
                            <td>Cash</td>
                            <td class="text-end">${debitAmount}</td>
                            <td class="text-end">${creditAmount}</td>
                            <td>-</td>
                            <td><span class="badge bg-light text-dark">${storeName}</span></td>
                            <td>${entry.created_by}</td>
                        `;
                        tbody.appendChild(row);
                    });
                    
                    // Update status message
                    const statusDiv = document.querySelector('.results-section .text-muted');
                    if (statusDiv) {
                        statusDiv.textContent = `Showing ${data.data.length} transactions`;
                    }
                    
                    console.log('SUCCESS: Loaded', data.data.length, 'transactions');
                } else {
                    console.error('Table body not found');
                }
            } else {
                console.error('API Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
        });
}

// Execute immediately
loadTransactionsNow();

// Execute after 1 second
setTimeout(loadTransactionsNow, 1000);

// Execute after 2 seconds
setTimeout(loadTransactionsNow, 2000);
