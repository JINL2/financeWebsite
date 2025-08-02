/**
 * Balance Sheet Frontend JavaScript - UI Only Version
 * API calls removed, UI functionality only
 */

// 전역 변수
let currentData = null;
let isLoading = false;
let urlParams = new URLSearchParams(window.location.search);
let currentUserId = urlParams.get('user_id');
let currentCompanyId = urlParams.get('company_id');

// 날짜 관련 변수들 - 범위 선택 지원
let selectedStartDate = null;
let selectedEndDate = null;
let currentYear = new Date().getFullYear();
let currentMonth = new Date().getMonth() + 1;
let calendarMode = 'range'; // 'range' for date range selection

// 월 이름 배열
const months = {
    1: 'January', 2: 'February', 3: 'March', 4: 'April',
    5: 'May', 6: 'June', 7: 'July', 8: 'August',
    9: 'September', 10: 'October', 11: 'November', 12: 'December'
};

// 이번 달 1일부터 마지막 날까지 기본 설정
function setDefaultDateRange() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth(); // 0-based
    
    selectedStartDate = new Date(year, month, 1);
    selectedEndDate = new Date(year, month + 1, 0); // 이번 달 마지막 날
    
    updateDatePickerDisplay();
    console.log('📅 Default date range set:', {
        start: formatDate(selectedStartDate),
        end: formatDate(selectedEndDate)
    });
}

// 날짜 포맷팅 함수
function formatDate(date) {
    if (!date) return '';
    return date.toISOString().split('T')[0]; // YYYY-MM-DD 형식
}

// 날짜 표시 포맷팅 함수
function formatDisplayDate(date) {
    if (!date) return '';
    const month = months[date.getMonth() + 1];
    return `${month} ${date.getDate()}, ${date.getFullYear()}`;
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    initializePage();
});

/**
 * 페이지 초기화 - 페이지스테이트 데이터와 연동
 */
function initializePage() {
    console.log('🚀 Initializing Balance Sheet page...');
    
    // 현재 날짜로 초기화
    const today = new Date();
    selectedYear = today.getFullYear();
    selectedMonth = today.getMonth() + 1;
    
    // URL 파라미터에서 회사 ID 가져오기
    if (currentCompanyId) {
        document.getElementById('companyFilter').value = currentCompanyId;
        document.getElementById('companySelect').value = currentCompanyId;
    }
    
    // 날짜 선택기 초기화
    initializeDateRangePicker();
    
    // 이벤트 리스너 등록
    setupEventListeners();
    
    // 페이지스테이트에서 회사/매장 데이터 로드
    loadPageStateData();
    
    // 초기 UI 설정 - 로딩 숨기고 빈 상태 표시
    hideLoading();
    showEmptyState();
    hideBalanceSheetContent();
    hideError();
    
    console.log('✅ Balance Sheet page initialization completed');
}

/**
 * 페이지스테이트에서 회사/매장 데이터 로드
 */
function loadPageStateData() {
    console.log('📊 Loading page state data...');
    
    // 1. SessionStorage에서 데이터 로드 시도
    try {
        const storedData = sessionStorage.getItem('userCompaniesData');
        if (storedData) {
            const userData = JSON.parse(storedData);
            console.log('📱 Found user data in SessionStorage:', userData);
            
            if (userData.companies) {
                populateCompanyDropdown(userData.companies);
                const currentCompany = userData.companies.find(c => c.company_id === currentCompanyId);
                if (currentCompany && currentCompany.stores) {
                    populateStoreDropdown(currentCompany.stores);
                }
                return; // 데이터를 찾았으면 종료
            }
        }
    } catch (error) {
        console.error('Error loading from SessionStorage:', error);
    }
    
    // 2. NavigationState에서 회사 데이터 확인 (백업)
    if (typeof window.NavigationState !== 'undefined' && window.NavigationState) {
        console.log('🧭 NavigationState found:', window.NavigationState);
        
        // NavigationState.userCompaniesData에서 회사 정보 확인
        if (window.NavigationState.userCompaniesData && window.NavigationState.userCompaniesData.companies) {
            console.log('🏢 Found companies in NavigationState.userCompaniesData');
            populateCompanyDropdown(window.NavigationState.userCompaniesData.companies);
            
            // 현재 선택된 회사의 매장 데이터 로드
            const currentCompany = window.NavigationState.userCompaniesData.companies.find(c => c.company_id === currentCompanyId);
            if (currentCompany && currentCompany.stores) {
                populateStoreDropdown(currentCompany.stores);
            }
            return;
        }
    }
    
    // userCompaniesData에서 회사 데이터 확인
    if (typeof userCompaniesData !== 'undefined' && userCompaniesData && userCompaniesData.companies) {
        console.log('🏢 userCompaniesData found:', userCompaniesData);
        
        populateCompanyDropdown(userCompaniesData.companies);
        
        // 현재 선택된 회사의 매장 데이터 로드
        const currentCompany = userCompaniesData.companies.find(c => c.company_id === currentCompanyId);
        if (currentCompany && currentCompany.stores) {
            populateStoreDropdown(currentCompany.stores);
        }
        return;
    }
    
    // 페이지스테이트 데이터를 기다려서 다시 시도
    console.log('⏳ Waiting for page state data...');
    setTimeout(() => {
        loadPageStateData();
    }, 500);
}

/**
 * Company 드롭다운 채우기
 */
function populateCompanyDropdown(companies) {
    console.log('🏢 Populating company dropdown with', companies.length, 'companies');
    
    const companyFilter = document.getElementById('companyFilter');
    const companySelect = document.getElementById('companySelect');
    
    if (!companyFilter || !companySelect) {
        console.error('Company dropdown elements not found');
        return;
    }
    
    // 기존 옵션 제거
    companyFilter.innerHTML = '';
    companySelect.innerHTML = '';
    
    // 기본 옵션 추가
    const defaultOption1 = document.createElement('option');
    defaultOption1.value = '';
    defaultOption1.textContent = 'Select Company';
    companyFilter.appendChild(defaultOption1);
    
    const defaultOption2 = document.createElement('option');
    defaultOption2.value = '';
    defaultOption2.textContent = 'Select Company';
    companySelect.appendChild(defaultOption2);
    
    // 회사 옵션 추가
    companies.forEach(company => {
        // Filter 드롭다운
        const option1 = document.createElement('option');
        option1.value = company.company_id;
        option1.textContent = company.company_name;
        option1.selected = company.company_id === currentCompanyId;
        companyFilter.appendChild(option1);
        
        // Navigation 드롭다운
        const option2 = document.createElement('option');
        option2.value = company.company_id;
        option2.textContent = company.company_name;
        option2.selected = company.company_id === currentCompanyId;
        companySelect.appendChild(option2);
        
        console.log(`  - ${company.company_name} (${company.company_id})${company.company_id === currentCompanyId ? ' [SELECTED]' : ''}`);
    });
    
    console.log('✅ Company dropdown populated successfully');
}

/**
 * Store 드롭다운 채우기
 */
function populateStoreDropdown(stores) {
    console.log('🏪 Populating store dropdown with', stores.length, 'stores');
    
    const storeFilter = document.getElementById('storeFilter');
    
    if (!storeFilter) {
        console.error('Store dropdown element not found');
        return;
    }
    
    // 기존 옵션 제거
    storeFilter.innerHTML = '';
    
    // 기본 옵션 추가 (All Stores)
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'All Stores';
    defaultOption.selected = true;
    storeFilter.appendChild(defaultOption);
    
    // 매장 옵션 추가
    stores.forEach(store => {
        const option = document.createElement('option');
        option.value = store.store_id;
        option.textContent = store.store_name;
        storeFilter.appendChild(option);
        
        console.log(`  - ${store.store_name} (${store.store_id})`);
    });
    
    console.log('✅ Store dropdown populated successfully');
}

/**
 * 회사 변경 시 매장 목록 업데이트
 */
function updateStoreListForCompany(companyId) {
    if (!companyId) {
        // 회사가 선택되지 않은 경우 매장 목록 초기화
        const storeFilter = document.getElementById('storeFilter');
        if (storeFilter) {
            storeFilter.innerHTML = '<option value="" selected>All Stores</option>';
        }
        return;
    }
    
    console.log('🔄 Updating store list for company:', companyId);
    
    // NavigationState.userCompaniesData에서 해당 회사 찾기
    if (typeof window.NavigationState !== 'undefined' && 
        window.NavigationState.userCompaniesData && 
        window.NavigationState.userCompaniesData.companies) {
        
        const selectedCompany = window.NavigationState.userCompaniesData.companies.find(c => c.company_id === companyId);
        
        if (selectedCompany && selectedCompany.stores) {
            console.log('🏪 Found', selectedCompany.stores.length, 'stores for company:', selectedCompany.company_name);
            populateStoreDropdown(selectedCompany.stores);
        } else {
            console.log('🏪 No stores found for company:', companyId);
            // 매장이 없는 경우 기본 옵션만 표시
            const storeFilter = document.getElementById('storeFilter');
            if (storeFilter) {
                storeFilter.innerHTML = '<option value="" selected>All Stores</option>';
            }
        }
        return;
    }
    
    // userCompaniesData에서 해당 회사 찾기 (폴백)
    if (typeof userCompaniesData !== 'undefined' && userCompaniesData && userCompaniesData.companies) {
        const selectedCompany = userCompaniesData.companies.find(c => c.company_id === companyId);
        
        if (selectedCompany && selectedCompany.stores) {
            console.log('🏪 Found', selectedCompany.stores.length, 'stores for company:', selectedCompany.company_name);
            populateStoreDropdown(selectedCompany.stores);
        } else {
            console.log('🏪 No stores found for company:', companyId);
            // 매장이 없는 경우 기본 옵션만 표시
            const storeFilter = document.getElementById('storeFilter');
            if (storeFilter) {
                storeFilter.innerHTML = '<option value="" selected>All Stores</option>';
            }
        }
        return;
    }
    
    console.log('⚠️ No company data available for store update');
}

/**
 * 이벤트 리스너 설정 - Supabase RPC 호출 추가
 */
function setupEventListeners() {
    // Search 버튼 클릭 시 Balance Sheet 데이터 로드
    document.getElementById('refreshBtn').addEventListener('click', function() {
        console.log('🔍 Search button clicked');
        loadBalanceSheetData();
    });
    
    // 회사 선택 변경
    document.getElementById('companyFilter').addEventListener('change', function() {
        const companyId = this.value;
        document.getElementById('companySelect').value = companyId;
        console.log('🏢 Company changed to:', companyId);
        
        // 회사 변경 시 해당 회사의 매장 목록 업데이트
        updateStoreListForCompany(companyId);
    });
    
    // 네비게이션 회사 선택 변경
    document.getElementById('companySelect').addEventListener('change', function() {
        const companyId = this.value;
        document.getElementById('companyFilter').value = companyId;
        console.log('🏢 Company changed to:', companyId);
        
        // 회사 변경 시 해당 회사의 매장 목록 업데이트
        updateStoreListForCompany(companyId);
    });
    
    // 매장 선택 변경
    document.getElementById('storeFilter').addEventListener('change', function() {
        console.log('🏪 Store changed to:', this.value);
    });
    
    // 제로 밸런스 포함 옵션 변경
    document.getElementById('includeZeroBalance').addEventListener('change', function() {
        console.log('⚖️ Include zero balance changed to:', this.checked);
    });
}

/**
 * 숫자 포맷팅 (쉼표 추가)
 */
function formatNumber(number) {
    if (typeof number !== 'number') {
        number = parseFloat(number) || 0;
    }
    return new Intl.NumberFormat('en-US').format(number);
}

/**
 * 계정 상세 보기 함수
 */
function showAccountDetails(accountId, accountName, balance, transactionCount) {
    console.log(`🔍 Account Details clicked:`, {
        id: accountId,
        name: accountName,
        balance: balance,
        transactions: transactionCount
    });
    
    // 간단한 알림으로 계정 정보 표시
    const message = `Account: ${accountName}\nBalance: ${getCurrentCurrencySymbol()}${formatCurrency(parseFloat(balance))}\nTransactions: ${transactionCount}`;
    alert(message);
    
    // TODO: 나중에 모달이나 상세 페이지로 확장 가능
}

/**
 * HTML 이스케이프 (중복 제거)
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 로딩 표시
 */
function showLoading() {
    console.log('🔄 Showing loading state...');
    isLoading = true;
    const loadingSpinner = document.getElementById('loadingSpinner');
    const refreshBtn = document.getElementById('refreshBtn');
    
    if (loadingSpinner) {
        loadingSpinner.classList.remove('d-none');
    }
    
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Loading...';
    }
    
    // 다른 콘텐츠 숨기기
    hideEmptyState();
    hideBalanceSheetContent();
    hideError();
}

/**
 * 로딩 숨김
 */
function hideLoading() {
    console.log('✅ Hiding loading state...');
    isLoading = false;
    const loadingSpinner = document.getElementById('loadingSpinner');
    const refreshBtn = document.getElementById('refreshBtn');
    
    if (loadingSpinner) {
        loadingSpinner.classList.add('d-none');
    }
    
    if (refreshBtn) {
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = '<i class="bi bi-search me-2"></i> Search';
    }
}

/**
 * 오류 메시지 표시
 */
function showError(message) {
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    
    errorMessage.textContent = message;
    errorAlert.classList.remove('d-none');
    
    // 5초 후 자동 숨김
    setTimeout(() => {
        hideError();
    }, 5000);
}

/**
 * 오류 메시지 숨김
 */
function hideError() {
    document.getElementById('errorAlert').classList.add('d-none');
}

/**
 * 재무상태표 콘텐츠 표시
 */
function showBalanceSheetContent() {
    document.getElementById('balanceSheetContent').style.display = 'block';
    document.getElementById('emptyState').classList.add('d-none');
}

/**
 * 재무상태표 콘텐츠 숨김
 */
function hideBalanceSheetContent() {
    document.getElementById('balanceSheetContent').style.display = 'none';
    document.getElementById('emptyState').classList.remove('d-none');
}

/**
 * 성공 메시지 표시 (중복 대비 제거)
 */
function showSuccessMessage(message, duration = 3000) {
    console.log(`✅ Success: ${message}`);
    
    // 기존 메시지 제거
    const existingMessage = document.querySelector('.success-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // 새 메시지 생성
    const messageDiv = document.createElement('div');
    messageDiv.className = 'alert alert-success success-message position-fixed';
    messageDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease-out;
    `;
    messageDiv.innerHTML = `
        <i class="bi bi-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(messageDiv);
    
    // 자동 제거
    setTimeout(() => {
        if (messageDiv.parentElement) {
            messageDiv.remove();
        }
    }, duration);
}

console.log('✅ Balance Sheet Frontend 초기화 완료 (UI Only)');
console.log('📅 날짜 선택기 기능 활성화');

// 페이지 로드 즉시 로딩 상태 강제 해제
setTimeout(() => {
    const loadingSpinner = document.getElementById('loadingSpinner');
    if (loadingSpinner && !loadingSpinner.classList.contains('d-none')) {
        console.log('🛑 Forcing loading spinner to hide on page load');
        loadingSpinner.classList.add('d-none');
    }
}, 100);

// ===== 날짜 선택기 기능 =====

/**
 * 달력 초기화 함수
 */
function initializeDatePicker() {
    const datePicker = document.getElementById('datePicker');
    const calendarDropdown = document.getElementById('calendarDropdown');
    
    if (!datePicker || !calendarDropdown) return;
    
    // Show/hide calendar on click
    datePicker.addEventListener('click', function(e) {
        e.stopPropagation();
        const isVisible = calendarDropdown.classList.contains('show');
        if (isVisible) {
            hideCalendar();
        } else {
            showCalendar();
        }
    });
    
    // Hide calendar when clicking outside
    document.addEventListener('click', function(e) {
        if (!calendarDropdown.contains(e.target) && e.target !== datePicker) {
            hideCalendar();
        }
    });
    
    // ESC key to close calendar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && calendarDropdown.classList.contains('show')) {
            hideCalendar();
        }
    });
    
    updateDatePickerDisplay();
}

function showCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    const datePicker = document.getElementById('datePicker');
    
    if (!calendarDropdown || !datePicker) return;
    
    calendarDropdown.classList.add('show');
}

function hideCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    if (calendarDropdown) {
        console.log('  📅 Hiding calendar...');
        calendarDropdown.classList.remove('show');
        calendarDropdown.style.display = 'none';
    }
}

// 날짜 범위 선택을 위한 새로운 달력 초기화
function initializeDateRangePicker() {
    console.log('📅 Initializing date range picker...');
    
    // 기본 날짜 범위 설정
    setDefaultDateRange();
    
    const datePicker = document.getElementById('datePicker');
    const calendarDropdown = document.getElementById('calendarDropdown');
    
    if (!datePicker || !calendarDropdown) {
        console.error('Date picker elements not found');
        return;
    }
    
    // 달력을 초기에 숨김 상태로 명시적으로 설정
    calendarDropdown.classList.remove('show');
    calendarDropdown.style.display = 'none';
    
    // 기존 이벤트 리스너 제거 (중복 방지)
    datePicker.removeEventListener('click', datePicker.clickHandler);
    
    // 새로운 이벤트 리스너 등록
    datePicker.clickHandler = function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('📅 Date picker clicked!');
        
        const isVisible = calendarDropdown.classList.contains('show');
        
        if (isVisible) {
            console.log('  Hiding calendar...');
            hideCalendar();
        } else {
            console.log('  Showing calendar...');
            showDateRangeCalendar();
        }
    };
    
    datePicker.addEventListener('click', datePicker.clickHandler);
    
    // 달력 외부 클릭 시 닫기
    document.addEventListener('click', function(e) {
        if (!datePicker.contains(e.target) && !calendarDropdown.contains(e.target)) {
            hideCalendar();
        }
    });
    
    // Quick Period Buttons 이벤트 등록
    setupQuickPeriodButtons();
    
    console.log('📅 Date range picker initialized successfully');
}

// 날짜 범위 달력 표시
function showDateRangeCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    if (!calendarDropdown) return;
    
    console.log('  📅 Displaying calendar...');
    calendarDropdown.style.display = 'block';
    calendarDropdown.classList.add('show');
}

// 새로운 날짜 범위 선택 시스템
function updateDatePickerDisplay() {
    const datePicker = document.getElementById('datePicker');
    if (datePicker && selectedStartDate && selectedEndDate) {
        if (isSameMonth(selectedStartDate, selectedEndDate)) {
            // 같은 달인 경우: "July 1-31, 2025"
            datePicker.value = `${formatDisplayDate(selectedStartDate).split(',')[0].replace(/\d+/, '')} ${selectedStartDate.getDate()}-${selectedEndDate.getDate()}, ${selectedStartDate.getFullYear()}`;
        } else {
            // 다른 달인 경우: "July 15, 2025 - August 14, 2025"
            datePicker.value = `${formatDisplayDate(selectedStartDate)} - ${formatDisplayDate(selectedEndDate)}`;
        }
    }
}

// 같은 달인지 확인하는 헬퍼 함수
function isSameMonth(date1, date2) {
    return date1.getFullYear() === date2.getFullYear() && 
           date1.getMonth() === date2.getMonth();
}

// Quick Period Buttons Setup
function setupQuickPeriodButtons() {
    const thisMonthBtn = document.getElementById('thisMonthBtn');
    const lastMonthBtn = document.getElementById('lastMonthBtn');
    const thisYearBtn = document.getElementById('thisYearBtn');
    
    if (thisMonthBtn) {
        thisMonthBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('📅 This Month button clicked');
            setThisMonth();
        });
    }
    
    if (lastMonthBtn) {
        lastMonthBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('📅 Last Month button clicked');
            setLastMonth();
        });
    }
    
    if (thisYearBtn) {
        thisYearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('📅 This Year button clicked');
            setThisYear();
        });
    }
}

// This Month function
function setThisMonth() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth(); // 0-based
    
    selectedStartDate = new Date(year, month, 1);
    selectedEndDate = new Date(year, month + 1, 0); // 이번 달 마지막 날
    
    updateDatePickerDisplay();
    hideCalendar();
    
    console.log('📅 This Month set:', {
        start: formatDate(selectedStartDate),
        end: formatDate(selectedEndDate)
    });
    
    showSuccessMessage('This month period selected');
}

// Last Month function
function setLastMonth() {
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth(); // 0-based
    
    // 지난 달 계산
    let lastMonthYear = year;
    let lastMonth = month - 1;
    
    if (lastMonth < 0) {
        lastMonth = 11; // 12월
        lastMonthYear = year - 1;
    }
    
    selectedStartDate = new Date(lastMonthYear, lastMonth, 1);
    selectedEndDate = new Date(lastMonthYear, lastMonth + 1, 0); // 지난 달 마지막 날
    
    updateDatePickerDisplay();
    hideCalendar();
    
    console.log('📅 Last Month set:', {
        start: formatDate(selectedStartDate),
        end: formatDate(selectedEndDate)
    });
    
    showSuccessMessage('Last month period selected');
}

// This Year function
function setThisYear() {
    const today = new Date();
    const year = today.getFullYear();
    
    selectedStartDate = new Date(year, 0, 1); // 1월 1일
    selectedEndDate = new Date(year, 11, 31); // 12월 31일
    
    updateDatePickerDisplay();
    hideCalendar();
    
    console.log('📅 This Year set:', {
        start: formatDate(selectedStartDate),
        end: formatDate(selectedEndDate)
    });
    
    showSuccessMessage('This year period selected');
}

/**
 * Balance Sheet 데이터 로드 함수 - Supabase RPC 호출
 */
async function loadBalanceSheetData() {
    try {
        // 파라미터 수집 및 검증
        const params = collectFilterParameters();
        
        if (!params.p_company_id || params.p_company_id === '') {
            showError('Please select a company before searching.');
            console.log('❌ No company selected. Current params:', params);
            return;
        }
        
        if (!params.p_start_date || !params.p_end_date) {
            showError('Please select a valid date range.');
            return;
        }
        
        console.log('📊 Loading Balance Sheet with parameters:', params);
        showLoading();
        hideError();
        
        // Supabase RPC 함수 호출
        const response = await callSupabaseRPC('get_balance_sheet', params);
        
        console.log('✅ Balance Sheet data received:', response);
        
        if (response && response.success) {
            currentData = response;
            console.log('✅ Balance Sheet data loaded successfully!');
            hideError();
            hideBalanceSheetContent(); // 먼저 숨기고
            renderBalanceSheetData(response); // 데이터 렌더링
            showBalanceSheetContent(); // 그 다음 보여주기
            
        } else {
            const errorMsg = response?.error?.message || 'Failed to load balance sheet data';
            showError(errorMsg);
            console.error('❌ Balance Sheet API Error:', response?.error);
        }
        
    } catch (error) {
        console.error('❌ Balance Sheet loading error:', error);
        showError(`Error loading balance sheet: ${error.message}`);
    } finally {
        hideLoading();
    }
}

/**
 * 필터 파라미터 수집 함수
 */
function collectFilterParameters() {
    // Company ID 가져오기 - 여러 소스에서 시도
    let companyId = document.getElementById('companyFilter')?.value;
    
    if (!companyId || companyId === '') {
        companyId = document.getElementById('companySelect')?.value;
    }
    
    if (!companyId || companyId === '') {
        companyId = currentCompanyId; // URL에서 가져온 값 사용
    }
    
    // Store ID 가져오기 (선택사항)
    const storeSelect = document.getElementById('storeFilter');
    const storeId = storeSelect?.value && storeSelect.value !== '' ? storeSelect.value : null;
    
    // 날짜 범위 가져오기
    let startDate, endDate;
    
    if (selectedStartDate && selectedEndDate) {
        startDate = formatDate(selectedStartDate);
        endDate = formatDate(selectedEndDate);
    } else {
        // 기본값: 이번 달
        const today = new Date();
        const year = today.getFullYear();
        const month = today.getMonth();
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        startDate = formatDate(firstDay);
        endDate = formatDate(lastDay);
    }
    
    // Include zero balance 옵션
    const includeZero = document.getElementById('includeZeroBalance')?.checked || false;
    
    const params = {
        p_company_id: companyId,
        p_start_date: startDate,
        p_end_date: endDate
    };
    
    // Store ID가 있으면 추가 (NULL이 아닌 경우에만)
    if (storeId && storeId !== '') {
        params.p_store_id = storeId;
    }
    
    console.log('📋 Collected filter parameters:', {
        company_id: companyId || 'NOT SET',
        store_id: storeId || 'null (All Stores)',
        start_date: startDate,
        end_date: endDate,
        include_zero: includeZero,
        currentCompanyId: currentCompanyId
    });
    
    // Supabase RPC 함수에 맞는 파라미터 구조로 반환
    const rpcParams = {
        p_company_id: companyId,
        p_start_date: startDate,
        p_end_date: endDate
    };
    
    // Store ID가 있으면 추가
    if (storeId && storeId !== '') {
        rpcParams.p_store_id = storeId;
    }
    
    return rpcParams;
}

/**
 * Supabase RPC 함수 호출
 */
async function callSupabaseRPC(functionName, parameters) {
    try {
        console.log(`🔗 Calling Supabase RPC: ${functionName}`);
        console.log('📤 Parameters:', parameters);
        
        // Supabase 프로젝트 정보
        const SUPABASE_URL = 'https://atkekzwgukdvucqntryo.supabase.co';
        const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8';
        
        const response = await fetch(`${SUPABASE_URL}/rest/v1/rpc/${functionName}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'apikey': SUPABASE_ANON_KEY,
                'Authorization': `Bearer ${SUPABASE_ANON_KEY}`,
                'Prefer': 'return=representation'
            },
            body: JSON.stringify(parameters)
        });
        
        console.log(`🔗 Response status: ${response.status}`);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Supabase API Error Response:', {
                status: response.status,
                statusText: response.statusText,
                url: response.url,
                body: errorText
            });
            
            let errorMessage = `Supabase API Error: ${response.status}`;
            
            try {
                const errorJson = JSON.parse(errorText);
                if (errorJson.message) {
                    errorMessage = errorJson.message;
                } else if (errorJson.details) {
                    errorMessage = errorJson.details;
                }
            } catch (e) {
                // errorText를 JSON으로 파싱할 수 없는 경우
                errorMessage = errorText || errorMessage;
            }
            
            throw new Error(errorMessage);
        }
        
        const data = await response.json();
        console.log('📥 Supabase RPC Response:', data);
        
        return data;
        
    } catch (error) {
        console.error('❌ Supabase RPC call failed:', error);
        throw error;
    }
}

/**
 * Balance Sheet 데이터 렌더링 함수
 */
function renderBalanceSheetData(data) {
    console.log('🎨 Rendering Balance Sheet data...');
    
    if (!data || !data.success) {
        console.error('❌ Invalid data for rendering:', data);
        return;
    }
    
    const balanceSheetData = data.data;
    const companyInfo = data.company_info;
    const uiData = data.ui_data; // 새로운 UI 데이터
    
    // 회사 정보 업데이트
    updateCompanyHeader(companyInfo, data.parameters);
    
    // Assets 섹션 렌더링
    renderAssetsSection(balanceSheetData);
    
    // Liabilities & Equity 섹션 렌더링
    renderLiabilitiesAndEquitySection(balanceSheetData);
    
    // 합계 업데이트
    updateTotalsSection(balanceSheetData.totals);
    
    // 새로운 UI 데이터 렌더링
    if (uiData) {
        try {
            renderChartsAndAnalytics(uiData);
        } catch (error) {
            console.warn('⚠️ Charts not available, continuing without charts:', error.message);
        }
        
        try {
            renderKPIIndicators(uiData.financial_ratios);
        } catch (error) {
            console.warn('⚠️ KPI indicators error:', error.message);
        }
        
        try {
            renderBalanceVerification(uiData.balance_verification);
        } catch (error) {
            console.warn('⚠️ Balance verification error:', error.message);
        }
        
        try {
            renderCompanyInfo(companyInfo, data.metadata);
        } catch (error) {
            console.warn('⚠️ Company info error:', error.message);
        }
    }
    
    console.log('✅ Balance Sheet rendering completed');
}

/**
 * 회사 헤더 업데이트
 */
function updateCompanyHeader(companyInfo, parameters) {
    const startDate = new Date(parameters.start_date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
    const endDate = new Date(parameters.end_date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
    
    // 페이지 제목 업데이트
    document.title = `Balance Sheet - ${companyInfo.company_name}`;
    
    console.log(`🏢 Company: ${companyInfo.company_name}`);
    console.log(`📅 Period: ${startDate} to ${endDate}`);
    console.log(`🏦 Store: ${companyInfo.store_name || 'All Stores'}`);
}

/**
 * Assets 섹션 렌더링
 */
function renderAssetsSection(data) {
    // Current Assets 렌더링
    renderAccountList('currentAssetsList', data.current_assets, '💵');
    
    // Non-Current Assets 렌더링
    renderAccountList('nonCurrentAssetsList', data.non_current_assets, '🏢');
    
    // Assets 합계 업데이트
    updateCategoryTotal('currentAssetsTotal', data.totals.total_current_assets);
    updateCategoryTotal('nonCurrentAssetsTotal', data.totals.total_non_current_assets);
    updateCategoryTotal('totalAssetsAmount', data.totals.total_assets);
}

/**
 * Liabilities & Equity 섹션 렌더링
 */
function renderLiabilitiesAndEquitySection(data) {
    // Current Liabilities 렌더링
    renderAccountList('currentLiabilitiesList', data.current_liabilities, '🗺');
    
    // Non-Current Liabilities 렌더링
    renderAccountList('nonCurrentLiabilitiesList', data.non_current_liabilities, '📄');
    
    // Equity 렌더링
    renderAccountList('equityList', data.equity, '💼');
    
    // 합계 업데이트
    updateCategoryTotal('totalLiabilitiesAmount', data.totals.total_liabilities);
    updateCategoryTotal('totalEquityAmount', data.totals.total_equity);
    updateCategoryTotal('totalLiabilitiesAndEquityAmount', data.totals.total_liabilities_and_equity);
}

/**
 * 계정 목록 렌더링
 */
function renderAccountList(containerId, accounts, icon = '💰') {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn(`⚠️ Container not found: ${containerId}`);
        return;
    }
    
    container.innerHTML = '';
    
    if (!accounts || accounts.length === 0) {
        container.innerHTML = `
            <div class="account-item text-muted">
                <span class="account-name">${icon} No accounts in this category</span>
                <span class="account-balance">${getCurrentCurrencySymbol()}0</span>
            </div>
        `;
        return;
    }
    
    accounts.forEach(account => {
        const accountElement = createAccountElement(account, icon);
        container.appendChild(accountElement);
    });
}

/**
 * 계정 요소 생성
 */
function createAccountElement(account, icon) {
    const div = document.createElement('div');
    div.className = 'account-item';
    div.setAttribute('data-account-id', account.account_id);
    
    // 비즈니스 로직: 0 잔액 계정 숨김 옵션 처리
    const includeZero = document.getElementById('includeZeroBalance')?.checked || false;
    if (!includeZero && account.balance === 0) {
        div.style.display = 'none';
    }
    
    // 음수 잔액 스타일링
    const isNegative = account.balance < 0;
    const balanceClass = isNegative ? 'text-danger' : '';
    
    div.innerHTML = `
        <div class="account-info">
            <span class="account-name">
                ${icon} ${account.account_name}
                ${account.transaction_count > 0 ? `<small class="text-muted">(${account.transaction_count} transactions)</small>` : ''}
            </span>
        </div>
        <span class="account-balance ${balanceClass}">
            ${getCurrentCurrencySymbol()}${account.formatted_balance}
        </span>
    `;
    
    // 클릭 이벤트 (상세 보기)
    div.addEventListener('click', () => {
        showAccountDetails(account);
    });
    
    return div;
}

/**
 * 카테고리 합계 업데이트
 */
function updateCategoryTotal(elementId, amount) {
    const element = document.getElementById(elementId);
    if (element) {
        const isNegative = amount < 0;
        const formattedAmount = formatCurrency(amount);
        element.textContent = `${getCurrentCurrencySymbol()}${formattedAmount}`;
        element.className = isNegative ? 'text-danger fw-bold' : 'fw-bold';
    }
}

/**
 * 전체 합계 섹션 업데이트
 */
function updateTotalsSection(totals) {
    // Balance Check 상태 표시
    const balanceCheck = totals.balance_check;
    const balanceDiff = totals.balance_difference;
    
    console.log(`⚖️ Balance Check: ${balanceCheck ? '✅ Balanced' : '❌ Unbalanced'}`);
    if (!balanceCheck) {
        console.warn(`⚠️ Balance Difference: ${getCurrentCurrencySymbol()}${formatCurrency(balanceDiff)}`);
    }
    
    // 필요시 Balance Alert 표시
    showBalanceAlert(balanceCheck, balanceDiff);
}

/**
 * Charts and Analytics 섹션 렌더링
 */
function renderChartsAndAnalytics(uiData) {
    console.log('📊 Rendering charts and analytics (Fallback)...');
    
    // Assets Composition Chart 대체
    if (uiData.assets_composition) {
        renderAssetsCompositionFallback(uiData.assets_composition);
    }
    
    // Liabilities vs Equity Chart 대체
    if (uiData.liabilities_vs_equity) {
        renderLiabilitiesVsEquityFallback(uiData.liabilities_vs_equity);
    }
    
    // Canvas 요소들을 div로 변경하여 차트가 보이도록 수정
    fixCanvasElements();
}

/**
 * Canvas 요소들을 수정하여 차트가 보이도록 하는 함수
 */
function fixCanvasElements() {
    // Assets Chart Canvas 수정
    const assetsCanvas = document.getElementById('assetsChart');
    if (assetsCanvas && assetsCanvas.tagName === 'CANVAS') {
        // Canvas 내용을 가져오기
        const canvasContent = assetsCanvas.innerHTML;
        if (canvasContent.trim()) {
            // Canvas를 div로 교체
            const chartDiv = document.createElement('div');
            chartDiv.id = 'assetsChart';
            chartDiv.className = 'chart-container';
            chartDiv.innerHTML = canvasContent;
            assetsCanvas.parentNode.replaceChild(chartDiv, assetsCanvas);
            console.log('✅ Assets chart canvas converted to div');
        }
    }
    
    // Liabilities vs Equity Chart Canvas 수정
    const liabEquityCanvas = document.getElementById('liabEquityChart');
    if (liabEquityCanvas && liabEquityCanvas.tagName === 'CANVAS') {
        // Canvas 내용을 가져오기
        const canvasContent = liabEquityCanvas.innerHTML;
        if (canvasContent.trim()) {
            // Canvas를 div로 교체
            const chartDiv = document.createElement('div');
            chartDiv.id = 'liabEquityChart';
            chartDiv.className = 'chart-container';
            chartDiv.innerHTML = canvasContent;
            liabEquityCanvas.parentNode.replaceChild(chartDiv, liabEquityCanvas);
            console.log('✅ Liabilities vs Equity chart canvas converted to div');
        }
    }
}

/**
 * Assets Composition Chart 렌더링
 */
function renderAssetsCompositionChart(assetsData) {
    const ctx = document.getElementById('assetsChart');
    if (!ctx) {
        console.warn('⚠️ Assets chart canvas not found');
        return;
    }
    
    // 기존 차트 파괴 (있는 경우)
    if (window.assetsChart instanceof Chart) {
        window.assetsChart.destroy();
    }
    
    try {
        window.assetsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Current Assets', 'Non-Current Assets'],
                datasets: [{
                    data: [
                        assetsData.current_assets_percentage,
                        assetsData.non_current_assets_percentage
                    ],
                    backgroundColor: ['#3498db', '#2ecc71'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const amount = label === 'Current Assets' ? 
                                    assetsData.current_assets_formatted : 
                                    assetsData.non_current_assets_formatted;
                                return `${label}: ${value.toFixed(1)}% (₫${amount})`;
                            }
                        }
                    }
                }
            }
        });
        console.log('✅ Assets composition chart rendered');
    } catch (error) {
        console.warn('⚠️ Chart.js not available, skipping chart:', error.message);
        // Chart.js가 없는 경우 텍스트로 대체
        ctx.parentElement.innerHTML = `
            <div class="chart-fallback">
                <h6>Assets Composition</h6>
                <div class="composition-item">
                    <span class="composition-label">💵 Current Assets:</span>
                    <span class="composition-value">${assetsData.current_assets_percentage}%</span>
                </div>
                <div class="composition-item">
                    <span class="composition-label">🏢 Non-Current Assets:</span>
                    <span class="composition-value">${assetsData.non_current_assets_percentage}%</span>
                </div>
            </div>
        `;
    }
}

/**
 * Liabilities vs Equity Chart 렌더링
 */
function renderLiabilitiesVsEquityChart(liabEquityData) {
    const ctx = document.getElementById('liabEquityChart');
    if (!ctx) {
        console.warn('⚠️ Liabilities vs Equity chart canvas not found');
        return;
    }
    
    // 기존 차트 파괴 (있는 경우)
    if (window.liabEquityChart instanceof Chart) {
        window.liabEquityChart.destroy();
    }
    
    try {
        window.liabEquityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Liabilities', 'Equity'],
                datasets: [{
                    label: 'Amount',
                    data: [
                        liabEquityData.total_liabilities_amount,
                        liabEquityData.total_equity_amount
                    ],
                    backgroundColor: ['#e74c3c', '#f39c12'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const percentage = label === 'Liabilities' ? 
                                    liabEquityData.liabilities_percentage : 
                                    liabEquityData.equity_percentage;
                                const formatted = label === 'Liabilities' ? 
                                    liabEquityData.total_liabilities_formatted : 
                                    liabEquityData.total_equity_formatted;
                                return `${label}: ${percentage}% (₫${formatted})`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₫' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        console.log('✅ Liabilities vs Equity chart rendered');
    } catch (error) {
        console.warn('⚠️ Chart.js not available, skipping chart:', error.message);
        // Chart.js가 없는 경우 텍스트로 대체
        ctx.parentElement.innerHTML = `
            <div class="chart-fallback">
                <h6>Liabilities vs Equity</h6>
                <div class="composition-item">
                    <span class="composition-label">📄 Liabilities:</span>
                    <span class="composition-value">${liabEquityData.liabilities_percentage}%</span>
                </div>
                <div class="composition-item">
                    <span class="composition-label">💼 Equity:</span>
                    <span class="composition-value">${liabEquityData.equity_percentage}%</span>
                </div>
            </div>
        `;
    }
}

/**
 * KPI Indicators 렌더링
 */
function renderKPIIndicators(ratiosData) {
    console.log('📈 Rendering KPI indicators...');
    
    // Debt Ratio
    updateKPICard('debtRatio', 'debtRatioBar', ratiosData.debt_ratio, '%');
    
    // Equity Ratio
    updateKPICard('equityRatio', 'equityRatioBar', ratiosData.equity_ratio, '%');
    
    // Current Ratio (Liquidity Ratio)
    const currentRatio = ratiosData.current_ratio;
    if (currentRatio !== null && currentRatio !== undefined) {
        updateKPICard('liquidityRatio', 'liquidityRatioBar', currentRatio, '%');
    } else {
        // Current Liabilities가 0인 경우
        document.getElementById('liquidityRatio').textContent = 'N/A';
        document.getElementById('liquidityRatioBar').style.width = '0%';
    }
    
    // Total Assets
    document.getElementById('totalAssetsKpi').textContent = ratiosData.total_assets_formatted ? 
        `₫${ratiosData.total_assets_formatted}` : '₫0';
    
    console.log('✅ KPI indicators rendered');
}

/**
 * KPI 카드 업데이트
 */
function updateKPICard(valueElementId, barElementId, value, suffix = '') {
    const valueElement = document.getElementById(valueElementId);
    const barElement = document.getElementById(barElementId);
    
    if (valueElement) {
        valueElement.textContent = `${value}${suffix}`;
    }
    
    if (barElement && typeof value === 'number') {
        // Progress bar 너비 설정 (최대 100%)
        const barWidth = Math.min(Math.max(value, 0), 100);
        barElement.style.width = `${barWidth}%`;
    }
}

/**
 * Balance Verification 섹션 렌더링
 */
function renderBalanceVerification(verificationData) {
    console.log('⚖️ Rendering balance verification...');
    
    const verificationIcon = document.getElementById('verificationIcon');
    const verificationTitle = document.getElementById('verificationTitle');
    const verificationMessage = document.getElementById('verificationMessage');
    const totalAssetsValue = document.getElementById('totalAssetsValue');
    const totalLiabEquityValue = document.getElementById('totalLiabEquityValue');
    const balanceDifference = document.getElementById('balanceDifference');
    const differenceDetail = document.getElementById('differenceDetail');
    const verificationCard = document.getElementById('balanceVerification');
    
    if (!verificationData) return;
    
    const isBalanced = verificationData.is_balanced;
    const difference = verificationData.difference;
    
    // 아이콘 및 스타일 업데이트
    if (verificationIcon) {
        if (isBalanced) {
            verificationIcon.innerHTML = '<i class="bi bi-check-circle"></i>';
            verificationIcon.className = 'verification-icon text-success';
        } else {
            verificationIcon.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';
            verificationIcon.className = 'verification-icon text-warning';
        }
    }
    
    // 제목 및 메시지 업데이트
    if (verificationTitle) {
        verificationTitle.textContent = isBalanced ? 'Balance Verification - Balanced' : 'Balance Verification - Unbalanced';
    }
    
    if (verificationMessage) {
        verificationMessage.textContent = isBalanced ? 
            'Assets equal Liabilities + Equity. The balance sheet is balanced.' :
            'Assets do not equal Liabilities + Equity. Please review the accounts.';
    }
    
    // 값 업데이트
    if (totalAssetsValue) {
        totalAssetsValue.textContent = verificationData.total_assets_formatted ? 
            `₫${verificationData.total_assets_formatted}` : '₫0';
    }
    
    if (totalLiabEquityValue) {
        totalLiabEquityValue.textContent = verificationData.total_liabilities_and_equity_formatted ? 
            `₫${verificationData.total_liabilities_and_equity_formatted}` : '₫0';
    }
    
    if (balanceDifference) {
        const diffFormatted = verificationData.difference_formatted || '0';
        balanceDifference.textContent = `₫${diffFormatted}`;
        balanceDifference.className = difference === 0 ? 'detail-value text-success' : 'detail-value text-danger';
    }
    
    // 차이가 0인 경우 차이 항목 숨기기
    if (differenceDetail) {
        differenceDetail.style.display = difference === 0 ? 'none' : 'block';
    }
    
    // 카드 전체 스타일 업데이트
    if (verificationCard) {
        verificationCard.className = isBalanced ? 
            'balance-verification-card balanced' : 
            'balance-verification-card unbalanced';
    }
    
    console.log(`⚖️ Balance verification: ${isBalanced ? 'Balanced' : 'Unbalanced'}`);
}

/**
 * Company Info 카드 렌더링
 */
function renderCompanyInfo(companyInfo, metadata) {
    console.log('🏢 Rendering company info...');
    
    const companyName = document.getElementById('companyName');
    const baseCurrency = document.getElementById('baseCurrency');
    const asOfDate = document.getElementById('asOfDate');
    const lastUpdate = document.getElementById('lastUpdate');
    
    if (companyName && companyInfo.company_name) {
        companyName.textContent = companyInfo.company_name;
    }
    
    if (baseCurrency && companyInfo.currency_code) {
        baseCurrency.textContent = `${companyInfo.currency_symbol} (${companyInfo.currency_code})`;
    }
    
    if (asOfDate && currentData && currentData.parameters) {
        const endDate = new Date(currentData.parameters.end_date);
        asOfDate.textContent = endDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    if (lastUpdate && metadata && metadata.calculation_date) {
        const updateDate = new Date(metadata.calculation_date);
        lastUpdate.textContent = updateDate.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    console.log('✅ Company info rendered');
}

/**
 * Balance Alert 표시
 */
function showBalanceAlert(isBalanced, difference) {
    // 기존 alert 제거
    const existingAlert = document.querySelector('.balance-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    if (!isBalanced && Math.abs(difference) > 0) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning balance-alert mt-3';
        alertDiv.innerHTML = `
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Balance Sheet Imbalance</h6>
            <p class="mb-0">Assets do not equal Liabilities + Equity. Difference: <strong>${getCurrentCurrencySymbol()}${formatCurrency(Math.abs(difference))}</strong></p>
        `;
        
        const container = document.getElementById('balanceSheetContent');
        if (container) {
            container.appendChild(alertDiv);
        }
    }
}

/**
 * 계정 상세 보기 (클릭 시)
 */
function showAccountDetails(account) {
    console.log(`🔍 Account Details: ${account.account_name}`);
    console.log(`   - Balance: ${getCurrentCurrencySymbol()}${account.formatted_balance}`);
    console.log(`   - Transactions: ${account.transaction_count}`);
    console.log(`   - Account ID: ${account.account_id}`);
    
    // TODO: 모달 또는 상세 페이지로 이동 기능 추가 가능
}

/**
 * Assets 섹션 렌더링
 */
function renderAssetsSection(data) {
    // Current Assets 렌더링
    renderAccountList('currentAssetsList', data.current_assets, '💵');
    
    // Non-Current Assets 렌더링
    renderAccountList('nonCurrentAssetsList', data.non_current_assets, '🏢');
    
    // Assets 합계 업데이트
    updateCategoryTotal('currentAssetsTotal', data.totals.total_current_assets);
    updateCategoryTotal('nonCurrentAssetsTotal', data.totals.total_non_current_assets);
    updateCategoryTotal('totalAssetsAmount', data.totals.total_assets);
}

/**
 * Liabilities & Equity 섹션 렌더링
 */
function renderLiabilitiesAndEquitySection(data) {
    // Current Liabilities 렌더링
    renderAccountList('currentLiabilitiesList', data.current_liabilities, '🗺');
    
    // Non-Current Liabilities 렌더링
    renderAccountList('nonCurrentLiabilitiesList', data.non_current_liabilities, '📄');
    
    // Equity 렌더링
    renderAccountList('equityList', data.equity, '💼');
    
    // 합계 업데이트
    updateCategoryTotal('currentLiabilitiesTotal', data.totals.total_current_liabilities);
    updateCategoryTotal('nonCurrentLiabilitiesTotal', data.totals.total_non_current_liabilities);
    updateCategoryTotal('totalLiabilitiesAmount', data.totals.total_liabilities);
    updateCategoryTotal('totalEquityAmount', data.totals.total_equity);
    updateCategoryTotal('totalLiabilitiesAndEquityAmount', data.totals.total_liabilities_and_equity);
}

/**
 * 계정 목록 렌더링
 */
function renderAccountList(containerId, accounts, icon = '💰') {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn(`⚠️ Container not found: ${containerId}`);
        return;
    }
    
    container.innerHTML = '';
    
    if (!accounts || accounts.length === 0) {
        container.innerHTML = `
            <div class="account-item text-muted">
                <span class="account-name">${icon} No accounts in this category</span>
                <span class="account-balance">${getCurrentCurrencySymbol()}0</span>
            </div>
        `;
        return;
    }
    
    accounts.forEach(account => {
        const accountElement = createAccountElement(account, icon);
        container.appendChild(accountElement);
    });
}

/**
 * 계정 요소 생성
 */
function createAccountElement(account, icon) {
    const div = document.createElement('div');
    div.className = 'account-item';
    div.setAttribute('data-account-id', account.account_id);
    
    // 비즈니스 로직: 0 잔액 계정 숨김 옵션 처리
    const includeZero = document.getElementById('includeZeroBalance')?.checked || false;
    if (!includeZero && account.balance === 0) {
        div.style.display = 'none';
    }
    
    // 음수 잔액 스타일링
    const isNegative = account.balance < 0;
    const balanceClass = isNegative ? 'text-danger' : '';
    
    div.innerHTML = `
        <div class="account-info">
            <span class="account-name">
                ${icon} ${account.account_name}
                ${account.transaction_count > 0 ? `<small class="text-muted">(${account.transaction_count} transactions)</small>` : ''}
            </span>
        </div>
        <span class="account-balance ${balanceClass}">
            ${getCurrentCurrencySymbol()}${account.formatted_balance}
        </span>
    `;
    
    // 클릭 이벤트 (상세 보기)
    div.addEventListener('click', () => {
        showAccountDetails(account);
    });
    
    return div;
}

/**
 * 카테고리 합계 업데이트
 */
function updateCategoryTotal(elementId, amount) {
    const element = document.getElementById(elementId);
    if (element) {
        const isNegative = amount < 0;
        const formattedAmount = formatCurrency(amount);
        element.textContent = `${getCurrentCurrencySymbol()}${formattedAmount}`;
        element.className = isNegative ? 'text-danger fw-bold' : 'fw-bold';
    }
}

/**
 * 전체 합계 섹션 업데이트
 */
function updateTotalsSection(totals) {
    // Balance Check 상태 표시
    const balanceCheck = totals.balance_check;
    const balanceDiff = totals.balance_difference;
    
    console.log(`⚖️ Balance Check: ${balanceCheck ? '✅ Balanced' : '❌ Unbalanced'}`);
    if (!balanceCheck) {
        console.warn(`⚠️ Balance Difference: ${getCurrentCurrencySymbol()}${formatCurrency(balanceDiff)}`);
    }
    
    // 필요시 Balance Alert 표시
    showBalanceAlert(balanceCheck, balanceDiff);
}

/**
 * Balance Alert 표시
 */
function showBalanceAlert(isBalanced, difference) {
    // 기존 alert 제거
    const existingAlert = document.querySelector('.balance-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    if (!isBalanced && Math.abs(difference) > 0) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning balance-alert mt-3';
        alertDiv.innerHTML = `
            <h6><i class="bi bi-exclamation-triangle me-2"></i>Balance Sheet Imbalance</h6>
            <p class="mb-0">Assets do not equal Liabilities + Equity. Difference: <strong>${getCurrentCurrencySymbol()}${formatCurrency(difference)}</strong></p>
        `;
        
        const container = document.querySelector('.balance-sheet-content');
        if (container) {
            container.appendChild(alertDiv);
        }
    }
}

/**
 * 계정 상세 보기 (클릭 시)
 */
function showAccountDetails(account) {
    console.log(`🔍 Account Details: ${account.account_name}`);
    console.log(`   - Balance: ${getCurrentCurrencySymbol()}${account.formatted_balance}`);
    console.log(`   - Transactions: ${account.transaction_count}`);
    console.log(`   - Account ID: ${account.account_id}`);
    
    // TODO: 모달 또는 상세 페이지로 이동 기능 추가 가능
}

/**
 * 통화 기호 가져오기
 */
function getCurrentCurrencySymbol() {
    if (currentData && currentData.company_info && currentData.company_info.currency_symbol) {
        return currentData.company_info.currency_symbol;
    }
    return '₫'; // 기본값
}

/**
 * 숫자 포맷팅 (세 자리마다 콤마)
 */
function formatCurrency(amount) {
    if (typeof amount !== 'number') {
        return '0';
    }
    return Math.abs(amount).toLocaleString('en-US');
}

/**
 * UI 상태 관리 함수들
 */
function showEmptyState() {
    console.log('📄 Showing empty state...');
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.classList.remove('d-none');
    }
}

function hideEmptyState() {
    console.log('💫 Hiding empty state...');
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.classList.add('d-none');
    }
}

function showBalanceSheetContent() {
    console.log('📊 Showing balance sheet content...');
    const content = document.getElementById('balanceSheetContent');
    if (content) {
        content.style.display = 'block';
        content.classList.remove('d-none');
    }
    hideEmptyState();
}

function hideBalanceSheetContent() {
    console.log('💫 Hiding balance sheet content...');
    const content = document.getElementById('balanceSheetContent');
    if (content) {
        content.style.display = 'none';
        content.classList.add('d-none');
    }
}

function showError(message) {
    console.log('❌ Showing error:', message);
    const errorAlert = document.getElementById('errorAlert');
    const errorMessage = document.getElementById('errorMessage');
    
    if (errorAlert && errorMessage) {
        errorMessage.textContent = message;
        errorAlert.classList.remove('d-none');
    }
    
    hideLoading();
}

function hideError() {
    console.log('✅ Hiding error...');
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) {
        errorAlert.classList.add('d-none');
    }
}

function showMessage(message) {
    console.log(`💬 ${message}`);
    // TODO: Toast 메시지 추가 가능
}

/**
 * Reset Filters function
 */
function resetFilters() {
    console.log('🔄 Resetting filters...');
    
    // Company filter reset
    const companyFilter = document.getElementById('companyFilter');
    if (companyFilter && currentCompanyId) {
        companyFilter.value = currentCompanyId;
    }
    
    // Store filter reset
    const storeFilter = document.getElementById('storeFilter');
    if (storeFilter) {
        storeFilter.value = '';
    }
    
    // Date range reset to this month
    setDefaultDateRange();
    
    // Zero balance option reset
    const includeZeroBalance = document.getElementById('includeZeroBalance');
    if (includeZeroBalance) {
        includeZeroBalance.checked = false;
    }
    
    // Hide any content and show empty state
    hideBalanceSheetContent();
    hideError();
    showEmptyState();
    
    showSuccessMessage('Filters have been reset');
    console.log('✅ Filters reset completed');
}

/**
 * Assets Composition Chart - 원형 차트 스타일 (개선된 버전)
 */
function renderAssetsCompositionFallback(assetsData) {
    // 여러운 방법으로 컨테이너 찾기
    let container = null;
    
    // 1번째 시도: ID로 찾기
    container = document.getElementById('assetsChart');
    
    // 2번째 시도: 헤더 텍스트로 찾기
    if (!container) {
        const headers = document.querySelectorAll('h6');
        headers.forEach(header => {
            if (header.textContent.includes('Assets Composition')) {
                container = header.closest('.chart-card') || header.parentElement;
            }
        });
    }
    
    // 3번째 시도: 차트 섹션 전체에서 찾기
    if (!container) {
        const chartsSection = document.querySelector('.chart-row, .charts-analytics, [class*="chart"]');
        if (chartsSection) {
            // 첫 번째 차트 자리에 삽입
            const firstChartSlot = chartsSection.querySelector('.col-md-6:first-child, .chart-container:first-child');
            if (firstChartSlot) {
                container = firstChartSlot;
            }
        }
    }
    
    // 4번째 시도: 첫 번째 col-md-6 요소 찾기
    if (!container) {
        const chartColumns = document.querySelectorAll('.col-md-6');
        if (chartColumns.length > 0) {
            container = chartColumns[0];
        }
    }
    
    // 5번째 시도: 대체 컨테이너 생성
    if (!container) {
        const mainContent = document.querySelector('#balanceSheetContent, .balance-sheet-content, .container');
        if (mainContent) {
            // 기존 차트 행 찾기
            let chartsRow = mainContent.querySelector('.chart-row, .charts-analytics');
            if (!chartsRow) {
                // 차트 행이 없으면 새로 생성
                chartsRow = document.createElement('div');
                chartsRow.className = 'row chart-row mt-4';
                chartsRow.innerHTML = `
                    <div class="col-md-6">
                        <div class="chart-card assets-chart-container">
                            <!-- Assets Composition Chart will be inserted here -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card liab-equity-chart-container">
                            <!-- Liabilities vs Equity Chart will be inserted here -->
                        </div>
                    </div>
                `;
                // KPI 섹션 바로 뒤에 삽입
                const kpiSection = mainContent.querySelector('.row.mb-4');
                if (kpiSection && kpiSection.nextSibling) {
                    mainContent.insertBefore(chartsRow, kpiSection.nextSibling);
                } else {
                    mainContent.appendChild(chartsRow);
                }
            }
            container = chartsRow.querySelector('.assets-chart-container');
        }
    }
    
    if (!container) {
        console.warn('⚠️ Assets chart container not found - creating fallback');
        const fallbackContainer = document.createElement('div');
        fallbackContainer.className = 'chart-card assets-chart-fallback mt-3';
        document.querySelector('#balanceSheetContent, body').appendChild(fallbackContainer);
        container = fallbackContainer;
    }
    
    const targetElement = container;
    
    // 동적 통화 기호 가져오기
    const currencySymbol = getCurrentCurrencySymbol();
    
    // Non-Current Assets 비율 계산 (두 지니를 합쳐서 100%가 되도록)
    const currentAssetsPercentage = assetsData.current_assets_percentage || 0;
    const nonCurrentAssetsPercentage = assetsData.non_current_assets_percentage || (100 - currentAssetsPercentage);
    
    // 가장 큰 비율의 섬션을 주 원형으로 표시
    const mainPercentage = Math.max(currentAssetsPercentage, nonCurrentAssetsPercentage);
    const mainColor = currentAssetsPercentage >= nonCurrentAssetsPercentage ? '#007bff' : '#6c757d';
    
    targetElement.innerHTML = `
        <div class="chart-content">
            <div class="circular-chart-container">
                <div class="circular-chart">
                    <svg viewBox="0 0 36 36" class="circular-chart-svg">
                        <path class="circle-bg"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none"
                            stroke="#eee"
                            stroke-width="3"/>
                        <path class="circle main-section"
                            stroke-dasharray="${mainPercentage}, 100"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none"
                            stroke="${mainColor}"
                            stroke-width="3"/>
                        <text x="18" y="20.35" class="percentage">${mainPercentage}%</text>
                    </svg>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #007bff;"></span>
                        <span class="legend-label">💵 Current Assets</span>
                        <span class="legend-value">${currencySymbol}${assetsData.current_assets_formatted || '0'}</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #6c757d;"></span>
                        <span class="legend-label">🏢 Non-Current Assets</span>
                        <span class="legend-value">${currencySymbol}${assetsData.non_current_assets_formatted || '0'}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    console.log('✅ Assets composition circular chart rendered (개선된 컨테이너 탐색)');
}

/**
 * Liabilities vs Equity Chart - 원형 차트 스타일 (수정된 버전)
 */
function renderLiabilitiesVsEquityFallback(liabEquityData) {
    // 여러운 방법으로 컨테이너 찾기
    let container = null;
    
    // 1번째 시도: ID로 찾기
    container = document.getElementById('liabEquityChart');
    
    // 2번째 시도: 헤더 텍스트로 찾기
    if (!container) {
        const headers = document.querySelectorAll('h6');
        headers.forEach(header => {
            if (header.textContent.includes('Liabilities vs Equity')) {
                container = header.closest('.chart-card') || header.parentElement;
            }
        });
    }
    
    // 3번째 시도: 차트 섹션에서 두 번째 컬럼 찾기
    if (!container) {
        const chartsSection = document.querySelector('.chart-row, .charts-analytics, [class*="chart"]');
        if (chartsSection) {
            // 두 번째 차트 자리에 삽입
            const secondChartSlot = chartsSection.querySelector('.col-md-6:nth-child(2), .chart-container:nth-child(2)');
            if (secondChartSlot) {
                container = secondChartSlot;
            } else {
                // liab-equity-chart-container 클래스로 찾기
                container = chartsSection.querySelector('.liab-equity-chart-container');
            }
        }
    }
    
    // 4번째 시도: 두 번째 col-md-6 요소 찾기
    if (!container) {
        const chartColumns = document.querySelectorAll('.col-md-6');
        if (chartColumns.length > 1) {
            container = chartColumns[1];
        } else if (chartColumns.length === 1) {
            // 하나밖에 없으면 그 안에 추가 컬럼 생성
            const newColumn = document.createElement('div');
            newColumn.className = 'col-md-6';
            newColumn.innerHTML = '<div class="chart-card liab-equity-chart-container"></div>';
            chartColumns[0].parentElement.appendChild(newColumn);
            container = newColumn.querySelector('.liab-equity-chart-container');
        }
    }
    
    // 5번째 시도: 대체 컨테이너 생성 또는 기존 차트 행에 추가
    if (!container) {
        const mainContent = document.querySelector('#balanceSheetContent, .balance-sheet-content, .container');
        if (mainContent) {
            // 기존 차트 행 찾기
            let chartsRow = mainContent.querySelector('.chart-row, .charts-analytics');
            if (!chartsRow) {
                // 차트 행이 없으면 새로 생성
                chartsRow = document.createElement('div');
                chartsRow.className = 'row chart-row mt-4';
                chartsRow.innerHTML = `
                    <div class="col-md-6">
                        <div class="chart-card assets-chart-container">
                            <!-- Assets Composition Chart -->
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-card liab-equity-chart-container">
                            <!-- Liabilities vs Equity Chart will be inserted here -->
                        </div>
                    </div>
                `;
                // KPI 섹션 바로 뒤에 삽입
                const kpiSection = mainContent.querySelector('.row.mb-4');
                if (kpiSection && kpiSection.nextSibling) {
                    mainContent.insertBefore(chartsRow, kpiSection.nextSibling);
                } else {
                    mainContent.appendChild(chartsRow);
                }
            }
            // liab-equity-chart-container 찾기
            container = chartsRow.querySelector('.liab-equity-chart-container');
            
            // 만약 없으면 새로 추가
            if (!container) {
                const newColumn = document.createElement('div');
                newColumn.className = 'col-md-6';
                newColumn.innerHTML = '<div class="chart-card liab-equity-chart-container"></div>';
                chartsRow.appendChild(newColumn);
                container = newColumn.querySelector('.liab-equity-chart-container');
            }
        }
    }
    
    if (!container) {
        console.warn('⚠️ Liabilities vs Equity chart container not found - creating fallback');
        const fallbackContainer = document.createElement('div');
        fallbackContainer.className = 'chart-card liab-equity-chart-fallback mt-3';
        document.querySelector('#balanceSheetContent, body').appendChild(fallbackContainer);
        container = fallbackContainer;
    }
    
    const targetElement = container;
    
    // 동적 통화 기호 가져오기
    const currencySymbol = getCurrentCurrencySymbol();
    
    // 실제 데이터에서 비율 계산 (음수 값 고려)
    const liabilitiesAmount = liabEquityData.total_liabilities_amount || 0;
    const equityAmount = liabEquityData.total_equity_amount || 0;
    
    // 절대값으로 비율 계산 (음수 equity 처리)
    const totalAbsolute = Math.abs(liabilitiesAmount) + Math.abs(equityAmount);
    let liabilitiesPercentage = 0;
    let equityPercentage = 0;
    
    if (totalAbsolute > 0) {
        liabilitiesPercentage = Math.round((Math.abs(liabilitiesAmount) / totalAbsolute) * 100);
        equityPercentage = Math.round((Math.abs(equityAmount) / totalAbsolute) * 100);
    }
    
    // 비율 검증 로그
    console.log('🔢 Liability/Equity 비율 계산:', {
        liabilities_amount: liabilitiesAmount,
        equity_amount: equityAmount,
        liabilities_percentage: liabilitiesPercentage,
        equity_percentage: equityPercentage
    });
    
    // 주요 섹션 결정: 더 큰 절대값을 가진 섹션
    const mainPercentage = Math.max(liabilitiesPercentage, equityPercentage);
    
    // 색상 로직 수정: Liabilities가 더 크면 빨간색, Equity가 더 크면 초록색
    const mainColor = (Math.abs(liabilitiesAmount) >= Math.abs(equityAmount)) ? '#dc3545' : '#28a745';
    
    // 범례에서 음수 표시 처리
    const liabilitiesValue = liabilitiesAmount >= 0 ? 
        `${currencySymbol}${liabEquityData.total_liabilities_formatted || '0'}` :
        `${currencySymbol}-${liabEquityData.total_liabilities_formatted || '0'}`;
        
    const equityValue = equityAmount >= 0 ? 
        `${currencySymbol}${liabEquityData.total_equity_formatted || '0'}` :
        `${currencySymbol}${liabEquityData.total_equity_formatted || '0'}`;
    
    targetElement.innerHTML = `
        <div class="chart-content">
            <div class="circular-chart-container">
                <div class="circular-chart">
                    <svg viewBox="0 0 36 36" class="circular-chart-svg">
                        <path class="circle-bg"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none"
                            stroke="#eee"
                            stroke-width="3"/>
                        <path class="circle main-section"
                            stroke-dasharray="${mainPercentage}, 100"
                            d="M18 2.0845
                              a 15.9155 15.9155 0 0 1 0 31.831
                              a 15.9155 15.9155 0 0 1 0 -31.831"
                            fill="none"
                            stroke="${mainColor}"
                            stroke-width="3"/>
                        <text x="18" y="20.35" class="percentage">${mainPercentage}%</text>
                    </svg>
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #dc3545;"></span>
                        <span class="legend-label">📄 Liabilities</span>
                        <span class="legend-value">${liabilitiesValue}</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color" style="background-color: #28a745;"></span>
                        <span class="legend-label">💼 Equity</span>
                        <span class="legend-value">${equityValue}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    console.log('✅ Liabilities vs Equity circular chart rendered (개선된 컨테이너 탐색 + 수정된 색상 로직)');
}
