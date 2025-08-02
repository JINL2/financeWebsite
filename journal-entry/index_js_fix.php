    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="advanced_search.js"></script>
    <script src="real_time_validation.js"></script>
    <script src="quick_actions_fix.js"></script>
    <script>
        // 🔍 Searchable Account Dropdown Functions
        let accountsData = []; // 전역 변수로 계정 데이터 저장
        let currentFocusedDropdown = null;
        
        // 계정 데이터 초기화
        function initializeAccountsData() {
            accountsData = <?php
                $jsAccountsData = [];
                foreach ($accounts as $account) {
                    $jsAccountsData[] = [
                        'id' => $account['account_id'],
                        'name' => $account['account_name'],
                        'type' => $account['account_type']
                    ];
                }
                echo json_encode($jsAccountsData);
            ?>;
            console.log('Accounts data initialized:', accountsData.length, 'accounts');
        }
        
        // 계정 필터링 함수
        function filterAccounts(input) {
            const query = input.value.toLowerCase();
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (query.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                const filteredAccounts = accountsData.filter(account => 
                    account.name.toLowerCase().includes(query)
                );
                showFilteredAccounts(dropdownList, filteredAccounts);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 모든 계정 표시
        function showAllAccounts(dropdownList) {
            const groupedAccounts = groupAccountsByType(accountsData);
            let html = '';
            
            Object.keys(groupedAccounts).forEach(type => {
                html += `<div class="account-dropdown-group">${type.charAt(0).toUpperCase() + type.slice(1)}</div>`;
                groupedAccounts[type].forEach(account => {
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            });
            
            dropdownList.innerHTML = html;
        }
        
        // 필터된 계정 표시
        function showFilteredAccounts(dropdownList, filteredAccounts) {
            let html = '';
            
            if (filteredAccounts.length === 0) {
                html = '<div class="account-dropdown-no-results">No accounts found</div>';
            } else {
                filteredAccounts.forEach(account => {
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            }
            
            dropdownList.innerHTML = html;
        }
        
        // 계정 선택 함수
        function selectAccount(item) {
            const accountId = item.dataset.accountId;
            const accountName = item.textContent;
            const container = item.closest('.account-dropdown-container');
            const input = container.querySelector('.account-search-input');
            const hiddenInput = container.querySelector('.account-id-hidden');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            input.value = accountName;
            hiddenInput.value = accountId;
            dropdownList.style.display = 'none';
            
            console.log('Account selected:', accountName, accountId);
            
            // 유효성 검사 실행
            validateForm();
        }
        
        // 드롭다운 표시
        function showAccountDropdown(input) {
            currentFocusedDropdown = input;
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (input.value.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                filterAccounts(input);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 드롭다운 숨기기
        function hideAccountDropdown(input) {
            setTimeout(() => {
                const container = input.closest('.account-dropdown-container');
                const dropdownList = container.querySelector('.account-dropdown-list');
                dropdownList.style.display = 'none';
                currentFocusedDropdown = null;
            }, 200);
        }
        
        // 계정 타입별 그룹화
        function groupAccountsByType(accounts) {
            const grouped = {};
            accounts.forEach(account => {
                if (!grouped[account.type]) {
                    grouped[account.type] = [];
                }
                grouped[account.type].push(account);
            });
            return grouped;
        }
        
        // 기본 유효성 검사 함수들
        function validateForm() {
            // 기본 유효성 검사 로직
            console.log('Form validation triggered');
        }
        
        function updateBalance() {
            // 잔액 계산 로직
            console.log('Balance update triggered');
        }
        
        function handleDebitCreditInput(input) {
            // 차변/대변 입력 처리 로직
            console.log('Debit/Credit input handled');
        }
        
        function addJournalLine() {
            // 새 라인 추가 로직
            console.log('Add journal line triggered');
        }
        
        function removeJournalLine(lineIndex) {
            // 라인 제거 로직
            console.log('Remove journal line triggered:', lineIndex);
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Initializing...');
            initializeAccountsData();
        });
        
        // 클릭 이벤트로 드롭다운 숨기기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.account-dropdown-container')) {
                document.querySelectorAll('.account-dropdown-list').forEach(list => {
                    list.style.display = 'none';
                });
            }
        });
    </script>

</body>
</html>
