/**
 * Journal Entry - Category Tag Handler
 * 계정 선택 시 category_tag에 따른 동적 UI 활성화
 */

// 전역 변수들 - journal_line_functions.js에서 이미 선언됨
// cashLocationsData와 counterpartiesData는 journal_line_functions.js에서 선언됨
// let cashLocationsData = []; // 중복 선언 제거
// let counterpartiesData = []; // 중복 선언 제거

/**
 * 계정 선택 시 호출되는 메인 함수
 * @param {string} accountId - 선택된 계정 ID  
 * @param {HTMLElement} lineElement - 해당 라인의 tr 요소
 * @param {string} categoryTag - 계정의 category_tag
 */
function onAccountSelected(accountId, lineElement, categoryTag) {
    console.log('Account selected:', accountId, 'Category Tag:', categoryTag);
    
    // 모든 확장 UI 초기화
    hideAllExtendedFields(lineElement);
    
    // Category Tag에 따른 UI 활성화
    switch(categoryTag) {
        case 'payable':
        case 'receivable':
            showCounterpartySelector(lineElement);
            showDebtDetailsButton(lineElement);
            console.log('Activated: Counterparty selector and debt details for', categoryTag);
            break;
            
        case 'cash':
            showCashLocationSelector(lineElement);
            loadCashLocationBalance(lineElement);
            console.log('Activated: Cash location selector');
            break;
            
        case 'fixedAsset':
            showFixedAssetDetailsButton(lineElement);
            console.log('Activated: Fixed asset details button');
            break;
            
        default:
            console.log('No special UI for category tag:', categoryTag);
            break;
    }
    
    // 유효성 검사 트리거
    if (typeof validateForm === 'function') {
        validateForm();
    }
}

/**
 * 모든 확장 필드 숨기기
 * @param {HTMLElement} lineElement 
 */
function hideAllExtendedFields(lineElement) {
    // Cash Location 숨기기
    const cashLocationSelect = lineElement.querySelector('.cash-location-select');
    if (cashLocationSelect) {
        cashLocationSelect.style.display = 'none';
        cashLocationSelect.value = '';
    }
    
    const locationContainer = lineElement.querySelector('.location-selector-container');
    if (locationContainer) {
        locationContainer.classList.remove('show');
    }
    
    // Counterparty 선택기 숨기기
    const counterpartyContainer = lineElement.querySelector('.counterparty-selector-container');
    if (counterpartyContainer) {
        counterpartyContainer.style.display = 'none';
    }
    
    // Details 버튼들 숨기기
    const detailsButtons = lineElement.querySelectorAll('.debt-details-button, .asset-details-button');
    detailsButtons.forEach(btn => btn.style.display = 'none');
    
    // Cash guidance 메시지 제거
    const guidanceMessage = lineElement.querySelector('.cash-guidance-message');
    if (guidanceMessage) {
        guidanceMessage.remove();
    }
    
    // Balance 정보 초기화
    const balanceInfo = lineElement.querySelector('.cash-balance-info');
    if (balanceInfo) {
        balanceInfo.innerHTML = '';
    }
    
    // Internal transaction 알림 제거
    const internalNotice = lineElement.querySelector('.internal-notice');
    if (internalNotice) {
        internalNotice.remove();
    }
}

/**
 * 거래처 선택기 표시 (Payable/Receivable용)
 * @param {HTMLElement} lineElement 
 */
function showCounterpartySelector(lineElement) {
    // 거래처 선택기가 없으면 동적으로 생성
    let counterpartyContainer = lineElement.querySelector('.counterparty-selector-container');
    if (!counterpartyContainer) {
        counterpartyContainer = createCounterpartySelector(lineElement);
    }
    
    counterpartyContainer.style.display = 'block';
    
    // 거래처 데이터가 로드되지 않았으면 로드
    if (counterpartiesData.length === 0) {
        loadCounterparties().then(() => {
            populateCounterpartyDropdown(counterpartyContainer);
        }).catch(error => {
            console.error('Failed to load counterparties:', error);
            showErrorMessage('Failed to load counterparties');
        });
    } else {
        populateCounterpartyDropdown(counterpartyContainer);
    }
}

/**
 * 채권/채무 상세 정보 버튼 표시
 * @param {HTMLElement} lineElement 
 */
function showDebtDetailsButton(lineElement) {
    // Details 버튼이 없으면 동적으로 생성
    let detailsButton = lineElement.querySelector('.debt-details-button');
    if (!detailsButton) {
        detailsButton = createDebtDetailsButton(lineElement);
    }
    
    detailsButton.style.display = 'inline-block';
}

/**
 * 현금 위치 선택기 표시
 * @param {HTMLElement} lineElement 
 */
async function showCashLocationSelector(lineElement) {
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
    
    // If no store is selected, show simplified message
    if (!storeId) {
        locationContainer.innerHTML = `
            <select class="form-select form-select-sm cash-location-select" disabled style="display: block;">
                <option value="">Select Store First</option>
            </select>
        `;
        console.log('Store not selected - showing store selection message');
        return;
    }
    
    try {
        // Load cash locations for the selected store
        const locations = await loadCashLocationsForStore(storeId);
        
        // Filter locations by type (cash/vault)
        const filteredLocations = locations.filter(location => {
            return location.location_type === 'cash' || location.location_type === 'vault';
        });
        
        // Create select element
        let selectHtml = '<select class="form-select form-select-sm cash-location-select">';
        selectHtml += '<option value="">Select Location</option>';
        
        filteredLocations.forEach(location => {
            const displayName = location.display_name || `${location.location_name} [${location.location_type}]`;
            selectHtml += `<option value="${location.cash_location_id}">${displayName}</option>`;
        });
        
        selectHtml += '</select>';
        
        locationContainer.innerHTML = selectHtml;
        
        // Add event listener to the new select and make it visible
        const newSelect = locationContainer.querySelector('.cash-location-select');
        if (newSelect) {
            // Make sure it's visible
            newSelect.style.display = 'block';
            
            if (!newSelect.hasAttribute('data-listener-added')) {
                newSelect.addEventListener('change', function() {
                    onCashLocationSelected(lineElement, this.value);
                });
                newSelect.setAttribute('data-listener-added', 'true');
            }
        }
        
        console.log(`Showing ${filteredLocations.length} cash locations for store ${storeId}`);
        
    } catch (error) {
        console.error('Error loading cash locations:', error);
        locationContainer.innerHTML = `
            <select class="form-select form-select-sm cash-location-select" disabled style="display: block;">
                <option value="">Error Loading Locations</option>
            </select>
        `;
    }
}

/**
 * 현금 위치 잔액 로드 및 표시
 * @param {HTMLElement} lineElement 
 */
function loadCashLocationBalance(lineElement) {
    // 잔액 정보를 표시할 요소가 없으면 생성
    let balanceInfo = lineElement.querySelector('.cash-balance-info');
    if (!balanceInfo) {
        balanceInfo = createCashBalanceInfo(lineElement);
    }
    
    // 실제 잔액 로드는 cash location이 선택되었을 때 수행
    balanceInfo.innerHTML = '<small class="text-muted">Select location to view balance</small>';
}

/**
 * 고정자산 상세 정보 버튼 표시
 * @param {HTMLElement} lineElement 
 */
function showFixedAssetDetailsButton(lineElement) {
    // Details 버튼이 없으면 동적으로 생성
    let detailsButton = lineElement.querySelector('.asset-details-button');
    if (!detailsButton) {
        detailsButton = createAssetDetailsButton(lineElement);
    }
    
    detailsButton.style.display = 'inline-block';
}

/**
 * 거래처 선택기 DOM 요소 생성
 * @param {HTMLElement} lineElement 
 * @returns {HTMLElement}
 */
function createCounterpartySelector(lineElement) {
    const descriptionCell = lineElement.children[2]; // Description 컬럼
    
    const container = document.createElement('div');
    container.className = 'counterparty-selector-container mt-2';
    container.style.display = 'none';
    
    container.innerHTML = `
        <label class="form-label form-label-sm mb-1">Counterparty:</label>
        <select class="form-select form-select-sm counterparty-select">
            <option value="">Select Counterparty</option>
        </select>
        <small class="text-info mt-1 d-block">
            <i class="bi bi-info-circle me-1"></i>Required for payable/receivable accounts
        </small>
    `;
    
    descriptionCell.appendChild(container);
    
    // 이벤트 리스너 추가
    const select = container.querySelector('.counterparty-select');
    select.addEventListener('change', function() {
        onCounterpartySelected(lineElement, this.value);
    });
    
    return container;
}

/**
 * 채권/채무 상세 정보 버튼 생성
 * @param {HTMLElement} lineElement 
 * @returns {HTMLElement}
 */
function createDebtDetailsButton(lineElement) {
    const descriptionCell = lineElement.children[2]; // Description 컬럼
    
    const button = document.createElement('button');
    button.className = 'btn btn-sm btn-outline-info debt-details-button mt-2';
    button.style.display = 'none';
    button.innerHTML = '<i class="bi bi-gear me-1"></i>Debt Details';
    
    button.addEventListener('click', function() {
        openDebtDetailsModal(lineElement);
    });
    
    descriptionCell.appendChild(button);
    return button;
}

/**
 * 고정자산 상세 정보 버튼 생성
 * @param {HTMLElement} lineElement 
 * @returns {HTMLElement}
 */
function createAssetDetailsButton(lineElement) {
    const descriptionCell = lineElement.children[2]; // Description 컬럼
    
    const button = document.createElement('button');
    button.className = 'btn btn-sm btn-outline-warning asset-details-button mt-2';
    button.style.display = 'none';
    button.innerHTML = '<i class="bi bi-tools me-1"></i>Asset Details';
    
    button.addEventListener('click', function() {
        openAssetDetailsModal(lineElement);
    });
    
    descriptionCell.appendChild(button);
    return button;
}

/**
 * 현금 잔액 정보 표시 요소 생성
 * @param {HTMLElement} lineElement 
 * @returns {HTMLElement}
 */
function createCashBalanceInfo(lineElement) {
    const locationCell = lineElement.children[5]; // Location 컬럼
    
    const balanceInfo = document.createElement('div');
    balanceInfo.className = 'cash-balance-info mt-1';
    
    locationCell.appendChild(balanceInfo);
    return balanceInfo;
}

/**
 * 현금 위치 드롭다운에 데이터 채우기
 * @param {HTMLElement} select 
 */
function populateCashLocationDropdown(select) {
    // 기본 옵션 유지
    select.innerHTML = '<option value="">Select Location</option>';
    
    // 현금 위치 옵션 추가
    cashLocationsData.forEach(location => {
        const option = document.createElement('option');
        option.value = location.cash_location_id;
        option.textContent = location.display_name || location.location_name;
        // 추가 정보를 데이터 속성에 저장
        option.dataset.locationType = location.location_type || '';
        option.dataset.currencyCode = location.currency_code || 'KRW';
        option.dataset.bankName = location.bank_name || '';
        select.appendChild(option);
    });
    
    // 이벤트 리스너 추가 (아직 없으면)
    if (!select.hasAttribute('data-listener-added')) {
        select.addEventListener('change', function() {
            const lineElement = this.closest('tr');
            onCashLocationSelected(lineElement, this.value);
        });
        select.setAttribute('data-listener-added', 'true');
    }
}

/**
 * 거래처 드롭다운에 데이터 채우기
 * @param {HTMLElement} container 
 */
function populateCounterpartyDropdown(container) {
    const select = container.querySelector('.counterparty-select');
    
    // 기본 옵션 유지
    select.innerHTML = '<option value="">Select Counterparty</option>';
    
    // 거래처 옵션 추가
    counterpartiesData.forEach(counterparty => {
        const option = document.createElement('option');
        option.value = counterparty.counterparty_id;
        option.textContent = counterparty.counterparty_name;
        
        // Internal 여부 표시
        if (counterparty.is_internal) {
            option.textContent += ' (Internal)';
            option.dataset.isInternal = 'true';
            option.style.backgroundColor = '#fef3c7';
            option.style.color = '#92400e';
        }
        
        // 회사 타입 정보 추가
        if (counterparty.company_type) {
            option.dataset.companyType = counterparty.company_type;
            if (!counterparty.is_internal) {
                option.textContent += ` [${counterparty.company_type.charAt(0).toUpperCase() + counterparty.company_type.slice(1)}]`;
            }
        }
        
        select.appendChild(option);
    });
}

/**
 * 거래처 선택 시 호출
 * @param {HTMLElement} lineElement 
 * @param {string} counterpartyId 
 */
function onCounterpartySelected(lineElement, counterpartyId) {
    if (!counterpartyId) return;
    
    const counterparty = counterpartiesData.find(c => c.counterparty_id === counterpartyId);
    
    if (counterparty && counterparty.is_internal) {
        // 내부 거래처 선택 시 추가 필드 활성화
        showInternalTransactionFields(lineElement);
    } else {
        hideInternalTransactionFields(lineElement);
    }
    
    console.log('Counterparty selected:', counterparty?.counterparty_name);
}

/**
 * 현금 위치 선택 시 호출
 * @param {HTMLElement} lineElement 
 * @param {string} locationId 
 */
function onCashLocationSelected(lineElement, locationId) {
    if (!locationId) {
        // 선택 해제 시 잔액 정보 숨기기
        const balanceInfo = lineElement.querySelector('.cash-balance-info');
        if (balanceInfo) {
            balanceInfo.innerHTML = '<small class="text-muted">Select location to view balance</small>';
        }
        return;
    }
    
    const location = cashLocationsData.find(l => l.cash_location_id === locationId);
    
    if (location) {
        // 잔액 정보 로드 및 표시
        loadAndDisplayCashBalance(lineElement, locationId);
        console.log('Cash location selected:', location.location_name || location.display_name);
    }
}

/**
 * 현금 잔액 로드 및 표시
 * @param {HTMLElement} lineElement 
 * @param {string} locationId 
 */
async function loadAndDisplayCashBalance(lineElement, locationId) {
    const balanceInfo = lineElement.querySelector('.cash-balance-info');
    if (!balanceInfo) return;
    
    // 로딩 표시
    balanceInfo.innerHTML = '<small class="text-muted"><i class="bi bi-arrow-clockwise spinner-border spinner-border-sm me-1"></i>Loading balance...</small>';
    
    try {
        // 현재 회사 ID 가져오기
        const companyId = getCompanyId();
        
        const response = await fetch(`get_cash_balance.php?cash_location_id=${encodeURIComponent(locationId)}&company_id=${encodeURIComponent(companyId)}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.data) {
            const balance = data.data;
            const currencySymbol = getCurrencySymbol(balance.currency_code);
            
            balanceInfo.innerHTML = `
                <small class="text-success">
                    <i class="bi bi-cash me-1"></i>Balance: ${currencySymbol}${balance.balance_formatted}
                </small>
                <br>
                <small class="text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-clock me-1"></i>Updated: ${formatTimeAgo(balance.last_updated)}
                </small>
            `;
            
            // 처음 3초간만 성공 애니메이션 표시
            balanceInfo.style.animation = 'fadeInSlide 0.3s ease-in-out';
            setTimeout(() => {
                balanceInfo.style.animation = '';
            }, 3000);
            
        } else {
            throw new Error(data.error || 'Failed to load balance');
        }
        
    } catch (error) {
        console.error('Error loading cash balance:', error);
        
        // 오류 시 fallback 데이터 사용
        const location = cashLocationsData.find(l => l.cash_location_id === locationId);
        const dummyBalance = location ? 
            (location.current_balance || (Math.random() * 100000).toFixed(2)) : 
            (Math.random() * 50000).toFixed(2);
            
        balanceInfo.innerHTML = `
            <small class="text-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>Balance: $${parseFloat(dummyBalance).toLocaleString()}
            </small>
            <br>
            <small class="text-muted" style="font-size: 0.75rem;">
                <i class="bi bi-info-circle me-1"></i>Demo data (API unavailable)
            </small>
        `;
    }
}

/**
 * 현금 계정 안내 메시지 표시
 * @param {HTMLElement} lineElement 
 */
function showCashGuidanceMessage(lineElement) {
    // 이미 있는 안내 메시지 제거
    const existingMessage = lineElement.querySelector('.cash-guidance-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const locationCell = lineElement.children[5]; // Location 컬럼
    
    const message = document.createElement('div');
    message.className = 'cash-guidance-message alert alert-info alert-sm mt-2 mb-0 p-2';
    message.innerHTML = `
        <i class="bi bi-info-circle me-1"></i>
        <small>Location required for cash account</small>
    `;
    
    locationCell.appendChild(message);
    
    // 5초 후 자동 숨김
    setTimeout(() => {
        if (message.parentNode) {
            message.classList.add('fade-out');
            setTimeout(() => message.remove(), 200);
        }
    }, 5000);
}

/**
 * 내부 거래 필드 표시
 * @param {HTMLElement} lineElement 
 */
function showInternalTransactionFields(lineElement) {
    const counterpartyContainer = lineElement.querySelector('.counterparty-selector-container');
    if (counterpartyContainer) {
        let internalNotice = counterpartyContainer.querySelector('.internal-notice');
        if (!internalNotice) {
            internalNotice = document.createElement('div');
            internalNotice.className = 'internal-notice alert alert-warning alert-sm mt-2 mb-0 p-2';
            internalNotice.innerHTML = `
                <i class="bi bi-building me-1"></i>
                <small><strong>Internal Transaction:</strong> Additional fields for inter-company transactions will be available here.</small>
            `;
            counterpartyContainer.appendChild(internalNotice);
        }
    }
}

/**
 * 내부 거래 필드 숨김
 * @param {HTMLElement} lineElement 
 */
function hideInternalTransactionFields(lineElement) {
    const internalNotice = lineElement.querySelector('.internal-notice');
    if (internalNotice) {
        internalNotice.remove();
    }
}

// Debt Details 모달은 details_modals.js에서 구현됨

// Asset Details 모달은 details_modals.js에서 구현됨

/**
 * Store가 선택된 store ID 가져오기
 * @returns {string|null}
 */
function getSelectedStoreId() {
    const storeSelect = document.querySelector('[name="store_id"]');
    return storeSelect ? storeSelect.value : null;
}

/**
 * 특정 store의 현금 위치 데이터 로드
 * @param {string} storeId 
 * @returns {Promise<Array>}
 */
async function loadCashLocationsForStore(storeId) {
    try {
        const companyId = getCompanyId();
        
        let url = `get_cash_locations.php?company_id=${encodeURIComponent(companyId)}&user_id=${encodeURIComponent(window.userId)}`;
        if (storeId) {
            url += `&store_id=${encodeURIComponent(storeId)}`;
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.success) {
            const locations = data.data || [];
            console.log('Cash locations loaded for store:', storeId, locations.length, 'locations');
            return locations;
        } else {
            throw new Error(data.error || 'Failed to load cash locations');
        }
    } catch (error) {
        console.error('Error loading cash locations:', error);
        // Return empty array on error instead of fallback data
        return [];
    }
}

/**
 * 현금 위치 데이터 로드 (backward compatibility)
 * @returns {Promise}
 */
async function loadCashLocations() {
    try {
        const companyId = getCompanyId();
        
        const response = await fetch(`get_cash_locations.php?company_id=${encodeURIComponent(companyId)}&user_id=${encodeURIComponent(window.userId)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.success) {
            cashLocationsData = data.data || [];
            console.log('Cash locations loaded:', cashLocationsData.length);
        } else {
            throw new Error(data.error || 'Failed to load cash locations');
        }
    } catch (error) {
        console.error('Error loading cash locations:', error);
        // Fallback to demo data if API fails
        if (!window.cashLocationsData) {
            createSampleData();
        }
        cashLocationsData = window.cashLocationsData || [];
        console.log('Using fallback demo data for cash locations');
    }
}

/**
 * 거래처 데이터 로드
 * @returns {Promise}
 */
async function loadCounterparties() {
    try {
        const companyId = getCompanyId();
        
        const response = await fetch(`get_counterparties.php?company_id=${encodeURIComponent(companyId)}&user_id=${encodeURIComponent(window.userId)}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        
        if (data.success) {
            // API 응답 형식을 내부 형식으로 변환
            counterpartiesData = (data.data || []).map(item => ({
                counterparty_id: item.counterparty_id,
                counterparty_name: item.counterparty_name || item.name,
                company_type: item.type,
                is_internal: item.is_internal || false,
                linked_company_id: item.linked_company_id || null
            }));
            console.log('Counterparties loaded from API:', counterpartiesData.length);
        } else {
            throw new Error(data.error || 'Failed to load counterparties');
        }
    } catch (error) {
        console.error('Error loading counterparties:', error);
        // Set empty array as fallback instead of demo data
        counterpartiesData = [];
        console.log('No counterparties available - using empty array');
    }
}

/**
 * 현재 회사 ID 가져오기
 * @returns {string}
 */
function getCompanyId() {
    // 다양한 방법으로 회사 ID 확인
    const storeSelect = document.getElementById('store_id');
    if (storeSelect && storeSelect.dataset.companyId) {
        return storeSelect.dataset.companyId;
    }
    
    // URL 파라미터에서 확인
    const urlParams = new URLSearchParams(window.location.search);
    const companyIdFromUrl = urlParams.get('company_id');
    if (companyIdFromUrl) {
        return companyIdFromUrl;
    }
    
    // 기본값 (PHP에서 설정된 값이 있다면)
    if (typeof window.currentCompanyId !== 'undefined') {
        return window.currentCompanyId;
    }
    
    // 최후의 수단으로 빈 문자열 반환
    console.warn('Company ID not found, using empty string');
    return '';
}

/**
 * 통화 코드에서 심볼 가져오기
 * @param {string} currencyCode 
 * @returns {string}
 */
function getCurrencySymbol(currencyCode) {
    const symbols = {
        'KRW': '₩',
        'USD': '$',
        'EUR': '€',
        'JPY': '¥',
        'GBP': '£',
        'CNY': '¥'
    };
    return symbols[currencyCode] || currencyCode;
}

/**
 * 시간 경과 표시 함수
 * @param {string} timestamp 
 * @returns {string}
 */
function formatTimeAgo(timestamp) {
    try {
        const now = new Date();
        const time = new Date(timestamp);
        const diffMs = now - time;
        const diffMins = Math.floor(diffMs / 60000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        
        const diffHours = Math.floor(diffMins / 60);
        if (diffHours < 24) return `${diffHours}h ago`;
        
        const diffDays = Math.floor(diffHours / 24);
        return `${diffDays}d ago`;
    } catch (error) {
        return 'Recently';
    }
}

/**
 * 에러 메시지 표시
 * @param {string} message 
 */
function showErrorMessage(message) {
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
        // fallback으로 console에 출력
        console.error('Error:', message);
    }
}

/**
 * 샘플 데이터 생성 (API 실패 시 fallback용)
 */
function createSampleData() {
    // 샘플 현금 위치 데이터
    window.cashLocationsData = [
        {
            cash_location_id: 'loc_001',
            location_name: 'Main Office Safe',
            display_name: 'Main Office Safe [Cash]',
            current_balance: 15420.50,
            location_type: 'cash',
            currency_code: 'KRW'
        },
        {
            cash_location_id: 'loc_002', 
            location_name: 'Petty Cash Box',
            display_name: 'Petty Cash Box [Cash]',
            current_balance: 2350.00,
            location_type: 'cash',
            currency_code: 'KRW'
        },
        {
            cash_location_id: 'loc_003',
            location_name: 'Branch Office Vault',
            display_name: 'Branch Office Vault [Cash]',
            current_balance: 45680.75,
            location_type: 'cash',
            currency_code: 'KRW'
        }
    ];

    // 거래처 데이터는 하드코딩된 데이터 대신 빈 배열로 설정
    window.counterpartiesData = [];

    console.log('Demo data created:', {
        cashLocations: window.cashLocationsData.length,
        counterparties: window.counterpartiesData.length
    });
}

/**
 * Store 선택 변경 시 모든 Cash 계정들의 location을 새로고침
 */
async function handleStoreChangeForCash() {
    console.log('Store selection changed - refreshing cash locations for all cash accounts');
    
    // 모든 journal line을 찾아서 cash 계정이 선택된 라인들을 업데이트
    const allLines = document.querySelectorAll('.journal-line');
    
    for (const line of allLines) {
        const accountIdInput = line.querySelector('.account-id-hidden');
        const accountId = accountIdInput ? accountIdInput.value : null;
        
        if (accountId) {
            // Cash 계정인지 확인 (category_tag 또는 account_id로 판단)
            const accountData = window.accountsData?.find(acc => acc.id === accountId);
            const isCashAccount = accountData?.category_tag === 'cash' || accountId === 'd4a7a16e-45a1-47fe-992b-ff807c8673f0';
            
            if (isCashAccount) {
                // Cash location selector를 다시 로드
                await showCashLocationSelector(line);
                console.log('Refreshed cash location for line:', line.querySelector('.line-number')?.textContent);
            }
        }
    }
}

/**
 * Store 선택 변경 이벤트 리스너 초기화
 */
function initializeStoreChangeListenerForCash() {
    const storeSelect = document.querySelector('[name="store_id"]');
    if (storeSelect) {
        storeSelect.addEventListener('change', handleStoreChangeForCash);
        console.log('Store change listener for cash locations initialized');
    } else {
        console.warn('Store select element not found for cash location handler');
    }
}

// DOM 로드 시 이벤트 리스너 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializeStoreChangeListenerForCash();
});
