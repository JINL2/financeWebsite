/**
 * Balance Sheet Frontend JavaScript
 * 완성된 Balance Sheet API와 연동하여 데이터 로드 및 렌더링
 * Updated to use page state parameters instead of API calls
 */

// 전역 변수
let currentData = null;
let isLoading = false;
let urlParams = new URLSearchParams(window.location.search);
let currentUserId = urlParams.get('user_id');
let currentCompanyId = urlParams.get('company_id');

// Global state for user companies and stores (from page state)
// userCompaniesData는 index.php에서 이미 선언되어 있으므로 중복 선언 제거

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

// Load user companies and stores from page state
async function loadUserCompaniesAndStores() {
    try {
        // Create form data for the RPC call
        const formData = new FormData();
        formData.append('action', 'get_user_companies_and_stores');
        formData.append('user_id', params.user_id);
        
        const response = await fetch('../common/supabase_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success && data.data) {
            userCompaniesData = data.data;
            updateCompanyDropdown();
            console.log('User companies loaded for Balance Sheet:', userCompaniesData);
        } else {
            console.error('Failed to load user companies:', data.error || 'Unknown error');
            // Fallback to current state if API fails
            document.getElementById('companySelect').innerHTML = 
                `<option value="${params.company_id}" selected>Current Company</option>`;
        }
    } catch (error) {
        console.error('Error loading user companies:', error);
        // Fallback to current state if API fails
        document.getElementById('companySelect').innerHTML = 
            `<option value="${params.company_id}" selected>Current Company</option>`;
    }
}

// Update company dropdown with loaded data
function updateCompanyDropdown() {
    const select = document.getElementById('companySelect');
    if (!userCompaniesData || !userCompaniesData.companies) {
        select.innerHTML = '<option value="">No companies available</option>';
        return;
    }
    
    select.innerHTML = '';
    
    userCompaniesData.companies.forEach(company => {
        const option = document.createElement('option');
        option.value = company.company_id;
        option.textContent = company.company_name;
        option.selected = company.company_id === params.company_id;
        select.appendChild(option);
    });
    
    console.log('Company dropdown updated for Balance Sheet');
}

// Change company function - now uses navigation enhancement
function changeCompany(companyId) {
    if (companyId && companyId !== params.company_id) {
        // Update navigation state for dynamic linking
        if (window.updateNavigationCompany) {
            window.updateNavigationCompany(companyId);
        }
        
        // Update local params
        params.company_id = companyId;
        params.store_id = null; // Reset store when company changes
        
        // Update URL
        const newUrl = `?user_id=${params.user_id}&company_id=${companyId}`;
        history.pushState(null, null, newUrl);
        
        // Update current variables
        currentCompanyId = companyId;
        
        // Reload balance sheet data
        loadBalanceSheet();
        
        console.log('Company changed in Balance Sheet to:', companyId);
    }
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    // Load companies first
    loadUserCompaniesAndStores();
    
    // Then initialize page
    initializePage();
});

/**
 * 페이지 초기화
 */
function initializePage() {
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
    initializeDatePicker();
    
    // 이벤트 리스너 등록
    setupEventListeners();
    
    // 회사 목록 로드
    loadCompanies();
    
    // 초기 매장 목록 로드 (회사가 선택된 경우)
    if (currentCompanyId) {
        loadStores(currentCompanyId);
    }
    
    // 초기 데이터 로드 (선택된 회사가 있는 경우에만)
    if (currentCompanyId) {
        // URL 파라미터의 회사 ID가 드롭다운에 존재하는지 확인
        setTimeout(() => {
            const companySelect = document.getElementById('companyFilter');
            const option = companySelect.querySelector(`option[value="${currentCompanyId}"]`);
            
            if (option) {
                // 존재하는 경우 선택 및 데이터 로드
                loadStores(currentCompanyId);
                loadBalanceSheet();
            } else {
                // 존재하지 않는 경우 오류 메시지 표시 및 로딩 숨김
                console.warn(`⚠️ Company ID '${currentCompanyId}' not found in company list`);
                showError(`Company with ID '${currentCompanyId}' not found. Please select a company from the dropdown.`);
                hideLoading();
                
                // URL의 company_id 파라미터 제거
                const url = new URL(window.location);
                url.searchParams.delete('company_id');
                window.history.replaceState({}, '', url);
                currentCompanyId = null;
            }
        }, 1000); // 회사 목록 로드 완료 후 체크
    } else {
        showError('Please select a company from the dropdown to view balance sheet data.');
        hideLoading();
    }
}

/**
 * 이벤트 리스너 설정
 */
function setupEventListeners() {
    // 새로고침 버튼
    document.getElementById('refreshBtn').addEventListener('click', function() {
        loadBalanceSheet();
    });
    
    // 회사 선택 변경
    document.getElementById('companyFilter').addEventListener('change', function() {
        const companyId = this.value;
        document.getElementById('companySelect').value = companyId;
        if (companyId) {
            loadStores(companyId); // 매장 목록 자동 로드
            loadBalanceSheet();
        }
    });
    
    // 네비게이션 회사 선택 변경
    document.getElementById('companySelect').addEventListener('change', function() {
        const companyId = this.value;
        document.getElementById('companyFilter').value = companyId;
        if (companyId) {
            loadStores(companyId); // 매장 목록 자동 로드
            loadBalanceSheet();
        }
    });
    
    // 매장 선택 변경
    document.getElementById('storeFilter').addEventListener('change', function() {
        loadBalanceSheet();
    });
    
    // 날짜 선택 변경 이벤트는 initializeDateSelectors()에서 처리됨
    
    // 제로 밸런스 포함 옵션 변경
    document.getElementById('includeZeroBalance').addEventListener('change', function() {
        loadBalanceSheet();
    });
}

/**
 * 회사 목록 로드
 */
async function loadCompanies() {
    try {
        const response = await fetch(`api_optimized.php?action=get_companies&user_id=${USER_ID}`);
        const result = await response.json();
        
        if (result.success) {
            const companyFilter = document.getElementById('companyFilter');
            const companySelect = document.getElementById('companySelect');
            
            // 기존 옵션 제거 (기본 옵션 제외)
            companyFilter.innerHTML = '<option value="">Select Company</option>';
            companySelect.innerHTML = '<option value="">Select Company...</option>';
            
            // 회사 옵션 추가
            result.data.companies.forEach(company => {
                const option1 = document.createElement('option');
                option1.value = company.company_id;
                option1.textContent = company.company_name;
                option1.selected = company.company_id === currentCompanyId;
                companyFilter.appendChild(option1);
                
                const option2 = document.createElement('option');
                option2.value = company.company_id;
                option2.textContent = company.company_name;
                option2.selected = company.company_id === currentCompanyId;
                companySelect.appendChild(option2);
            });
            
            console.log('회사 목록 로드 완료:', result.data.companies.length, '개 회사');
        } else {
            console.error('회사 목록 로드 실패:', result.error?.message);
        }
    } catch (error) {
        console.error('회사 목록 로드 오류:', error);
    }
}

/**
 * Balance Sheet API 호출 및 데이터 로드
 */
async function loadBalanceSheet() {
    if (isLoading) return;
    
    const companyId = document.getElementById('companyFilter').value;
    const storeId = document.getElementById('storeFilter').value;
    // 드롭다운에서 선택된 날짜 가져오기
    const selectedDate = getSelectedDate();
    const asOfDate = `${selectedDate.year}-${selectedDate.month.toString().padStart(2, '0')}`;
    const includeZero = document.getElementById('includeZeroBalance').checked;
    
    if (!companyId) {
        showError('Please select a company.');
        return;
    }
    
    try {
        showLoading();
        hideError();
        
        // API 파라미터 구성
        const params = new URLSearchParams({
            action: 'get_balance_sheet',
            company_id: companyId
        });
        
        if (storeId) {
            params.append('store_id', storeId);
        }
        
        if (asOfDate) {
            params.append('as_of_date', asOfDate);
        }
        
        if (includeZero) {
            params.append('include_zero', 'true');
        }
        
        const url = `api_supabase_function.php?${params.toString()}`;
        console.log('API call:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        
        console.log('API response:', data);
        
        if (data.success) {
            currentData = data;
            renderBalanceSheet(data);
            hideLoading();
            showBalanceSheetContent();
            updatePageSubtitle(data);
        } else {
            throw new Error(data.error?.message || 'An unknown error occurred.');
        }
    } catch (error) {
        console.error('API 오류:', error);
        showError(`Error loading data: ${error.message}`);
        hideLoading();
        hideBalanceSheetContent();
    }
}

/**
 * 재무상태표 데이터 렌더링
 */
function renderBalanceSheet(apiResponse) {
    // 새로운 API 응답 구조에 맞게 데이터 추출
    const data = apiResponse.data;
    const companyInfo = apiResponse.company_info;
    
    // 회사 정보 렌더링
    renderCompanyInfo(companyInfo, apiResponse.parameters);
    
    // 자산 섹션 렌더링
    renderAssets(data);
    
    // 부채 및 자본 섹션 렌더링
    renderLiabilitiesAndEquity(data);
    
    // 균형식 검증 렌더링
    renderBalanceVerification(data.totals);
    
    // 차트 및 KPI 업데이트
    updateChartsAndKPIs();
    
    // 애니메이션 효과 적용
    addFadeInAnimation();
}

/**
 * 회사 정보 렌더링
 */
function renderCompanyInfo(companyInfo, parameters) {
    document.getElementById('companyName').textContent = companyInfo.company_name || '-';
    document.getElementById('baseCurrency').textContent = `${companyInfo.currency_code} (${companyInfo.currency_symbol})` || '-';
    document.getElementById('asOfDate').textContent = `${parameters.start_date} to ${parameters.end_date}` || '-';
    document.getElementById('lastUpdate').textContent = new Date().toLocaleString('en-US');
}

/**
 * 자산 섹션 렌더링
 */
function renderAssets(data) {
    const currencySymbol = currentData?.company_info?.currency_symbol || '₫';
    
    // 자산 총계
    document.getElementById('assetTotal').textContent = `${currencySymbol}${formatNumber(data.totals.total_assets)}`;
    
    // 유동자산
    renderAccountList('currentAssetsList', data.current_assets, currencySymbol);
    document.getElementById('currentAssetsTotal').textContent = `${currencySymbol}${formatNumber(data.totals.total_current_assets)}`;
    
    // 비유동자산
    renderAccountList('nonCurrentAssetsList', data.non_current_assets, currencySymbol);
    document.getElementById('nonCurrentAssetsTotal').textContent = `${currencySymbol}${formatNumber(data.totals.total_non_current_assets)}`;
    
    // 섹션 표시/숨김 처리
    toggleSection('currentAssetsSection', data.current_assets?.length > 0);
    toggleSection('nonCurrentAssetsSection', data.non_current_assets?.length > 0);
}

/**
 * 부채 및 자본 섹션 렌더링
 */
function renderLiabilitiesAndEquity(data) {
    const currencySymbol = currentData?.company_info?.currency_symbol || '₫';
    
    // 부채+자본 총계
    document.getElementById('liabilitiesEquityTotal').textContent = `${currencySymbol}${formatNumber(data.totals.total_liabilities_and_equity)}`;
    
    // 유동부채
    renderAccountList('currentLiabilitiesList', data.current_liabilities, currencySymbol);
    
    // 비유동부채
    renderAccountList('nonCurrentLiabilitiesList', data.non_current_liabilities, currencySymbol);
    
    // 부채 총계
    document.getElementById('totalLiabilities').textContent = `${currencySymbol}${formatNumber(data.totals.total_liabilities)}`;
    
    // 자본
    renderAccountList('equityList', data.equity, currencySymbol);
    document.getElementById('totalEquity').textContent = `${currencySymbol}${formatNumber(data.totals.total_equity)}`;
    
    // 섹션 표시/숨김 처리
    toggleSection('currentLiabilitiesSection', data.current_liabilities?.length > 0);
    toggleSection('nonCurrentLiabilitiesSection', data.non_current_liabilities?.length > 0);
    toggleSection('liabilitiesSection', (data.current_liabilities?.length > 0) || (data.non_current_liabilities?.length > 0));
    toggleSection('equitySection', data.equity?.length > 0);
}

/**
 * 계정 목록 렌더링
 */
function renderAccountList(elementId, accounts, currencySymbol) {
    const container = document.getElementById(elementId);
    
    if (!accounts || accounts.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-2">No data available</div>';
        return;
    }
    
    let html = '';
    accounts.forEach(account => {
        const balance = account.balance || 0;
        const formattedBalance = account.formatted_balance || formatNumber(balance);
        
        html += `
            <div class="account-item">
                <div class="account-name">
                    <i class="bi bi-record-circle account-icon"></i>
                    ${escapeHtml(account.account_name)}
                </div>
                <div class="account-balance currency">
                    ${currencySymbol}${formattedBalance}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

/**
 * 균형식 검증 렌더링
 */
function renderBalanceVerification(totals) {
    const currencySymbol = currentData?.company_info?.currency_symbol || '₫';
    const verificationCard = document.getElementById('balanceVerification');
    const icon = document.getElementById('verificationIcon');
    const title = document.getElementById('verificationTitle');
    const message = document.getElementById('verificationMessage');
    
    // 총계 값 업데이트
    document.getElementById('totalAssetsValue').textContent = `${currencySymbol}${formatNumber(totals.total_assets)}`;
    document.getElementById('totalLiabEquityValue').textContent = `${currencySymbol}${formatNumber(totals.total_liabilities_and_equity)}`;
    document.getElementById('balanceDifference').textContent = `${currencySymbol}${formatNumber(Math.abs(totals.balance_difference || 0))}`;
    
    // 균형식 상태에 따른 스타일링
    if (totals.balance_check) {
        // 균형 맞음
        verificationCard.className = 'balance-verification-card success';
        icon.innerHTML = '<i class="bi bi-check-circle"></i>';
        title.textContent = 'Balance Equation Matched ✓';
        message.textContent = 'Assets = Liabilities + Equity equation is balanced correctly.';
        
        // 차이 항목 숨김
        document.getElementById('differenceDetail').style.display = 'none';
    } else {
        // 균형 안 맞음
        verificationCard.className = 'balance-verification-card error';
        icon.innerHTML = '<i class="bi bi-exclamation-triangle"></i>';
        title.textContent = 'Balance Equation Mismatch ⚠️';
        message.textContent = 'Assets and Liabilities + Equity do not match. Please check journal entries.';
        
        // 차이 항목 표시
        document.getElementById('differenceDetail').style.display = 'flex';
    }
}

/**
 * 페이지 부제목 업데이트
 */
function updatePageSubtitle(apiResponse) {
    const companyName = apiResponse.company_info?.company_name || 'Selected Company';
    const startDate = apiResponse.parameters?.start_date || 'Current';
    const endDate = apiResponse.parameters?.end_date || 'Current';
    const subtitle = `${companyName}'s Balance Sheet for ${startDate} to ${endDate}`;
    document.getElementById('pageSubtitle').textContent = subtitle;
}

/**
 * 섹션 표시/숨김 토글
 */
function toggleSection(sectionId, show) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.style.display = show ? 'block' : 'none';
    }
}

/**
 * 소계 계산
 */
function calculateSubtotal(accounts) {
    if (!accounts || !Array.isArray(accounts)) return 0;
    return accounts.reduce((sum, account) => sum + (account.balance || 0), 0);
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
 * HTML 이스케이프
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 로딩 표시
 */
function showLoading() {
    isLoading = true;
    document.getElementById('loadingSpinner').classList.remove('d-none');
    document.getElementById('refreshBtn').disabled = true;
    document.getElementById('refreshBtn').innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Loading...';
}

/**
 * 로딩 숨김
 */
function hideLoading() {
    isLoading = false;
    document.getElementById('loadingSpinner').classList.add('d-none');
    document.getElementById('refreshBtn').disabled = false;
    document.getElementById('refreshBtn').innerHTML = '<i class="bi bi-search me-2"></i> Search';
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
 * 페이드인 애니메이션 추가
 */
function addFadeInAnimation() {
    const cards = document.querySelectorAll('.balance-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
}

/**
 * 필터 초기화
 */
function resetFilters() {
    document.getElementById('companyFilter').value = currentCompanyId || '';
    document.getElementById('companySelect').value = currentCompanyId || '';
    document.getElementById('storeFilter').value = '';
    
    // 날짜를 현재 날짜로 리셋
    const today = new Date();
    selectedYear = today.getFullYear();
    selectedMonth = today.getMonth() + 1;
    
    // 날짜 표시 업데이트
    updatePeriodDisplay();
    
    document.getElementById('includeZeroBalance').checked = false;
    
    loadBalanceSheet();
}



/**
 * 회사 변경 (네비게이션에서 호출)
 */
function changeCompany(companyId) {
    document.getElementById('companyFilter').value = companyId;
    document.getElementById('companySelect').value = companyId;
    if (companyId) {
        loadBalanceSheet();
    }
}

/**
 * API 연결 테스트
 */
async function testApiConnection() {
    try {
        const response = await fetch('api.php?action=test');
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ API connection successful:', data.message);
            return true;
        } else {
            console.error('❌ API connection failed:', data.error);
            return false;
        }
    } catch (error) {
        console.error('❌ API connection error:', error);
        return false;
    }
}

/**
 * 디버그 정보 출력
 */
function debugInfo() {
    console.group('🔍 Balance Sheet Debug Info');
    console.log('Current data:', currentData);
    console.log('Loading state:', isLoading);
    console.log('Selected company:', document.getElementById('companyFilter').value);
    console.log('Selected store:', document.getElementById('storeFilter').value);
    console.log('Selected date:', document.getElementById('dateFilter').value);
    console.log('Include zero balance:', document.getElementById('includeZeroBalance').checked);
    console.groupEnd();
}

/**
 * 페이지 새로고침
 */
function refreshPage() {
    location.reload();
}

/**
 * 키보드 단축키 설정
 */
document.addEventListener('keydown', function(e) {
    // Ctrl + R 또는 F5: 데이터 새로고침
    if ((e.ctrlKey && e.key === 'r') || e.key === 'F5') {
        e.preventDefault();
        loadBalanceSheet();
    }
    
    // F12: 디버그 정보
    if (e.key === 'F12') {
        e.preventDefault();
        debugInfo();
    }
});

/**
 * 윈도우 크기 변경 시 레이아웃 조정
 */
window.addEventListener('resize', function() {
    // 모바일에서 데스크톱으로 전환 시 레이아웃 최적화
    const isMobile = window.innerWidth <= 768;
    const balanceGrid = document.querySelector('.balance-sheet-grid');
    
    if (balanceGrid) {
        if (isMobile) {
            balanceGrid.style.gridTemplateColumns = '1fr';
        } else {
            balanceGrid.style.gridTemplateColumns = '1fr 1fr';
        }
    }
    
    // 날짜 선택기 모달이 열려있으면 위치 재조정
    const calendarModal = document.getElementById('calendarModal');
    if (calendarModal && calendarModal.classList.contains('show')) {
        const periodSelector = document.getElementById('periodSelector');
        if (periodSelector) {
            const rect = periodSelector.getBoundingClientRect();
            calendarModal.style.top = (rect.bottom + 5) + 'px';
            calendarModal.style.left = rect.left + 'px';
        }
    }
});

/**
 * 페이지 언로드 시 정리
 */
window.addEventListener('beforeunload', function() {
    // 현재 진행 중인 요청이 있다면 취소
    if (isLoading) {
        // AbortController를 사용한 요청 취소 (미래 구현)
        console.log('페이지 언로드: 진행 중인 요청 취소');
    }
});

/**
 * 개발자를 위한 유틸리티 함수들
 */
window.balanceSheetDebug = {
    getCurrentData: () => currentData,
    getLoadingState: () => isLoading,
    testApi: testApiConnection,
    debugInfo: debugInfo,
    refreshData: loadBalanceSheet,
    resetFilters: resetFilters
};

/**
 * 성능 모니터링
 */
let performanceMetrics = {
    apiCallCount: 0,
    lastApiCallTime: null,
    averageResponseTime: 0,
    responseTimes: []
};

/**
 * API 호출 성능 측정
 */
function measureApiPerformance(startTime, endTime) {
    const responseTime = endTime - startTime;
    performanceMetrics.apiCallCount++;
    performanceMetrics.lastApiCallTime = new Date();
    performanceMetrics.responseTimes.push(responseTime);
    
    // 최근 10개 응답시간의 평균 계산
    if (performanceMetrics.responseTimes.length > 10) {
        performanceMetrics.responseTimes.shift();
    }
    
    performanceMetrics.averageResponseTime = 
        performanceMetrics.responseTimes.reduce((a, b) => a + b, 0) / performanceMetrics.responseTimes.length;
    
    console.log(`📊 API 성능: ${responseTime}ms (평균: ${Math.round(performanceMetrics.averageResponseTime)}ms)`);
}

// 성능 측정을 위해 loadBalanceSheet 함수 수정
const originalLoadBalanceSheet = loadBalanceSheet;
loadBalanceSheet = async function() {
    const startTime = performance.now();
    try {
        await originalLoadBalanceSheet();
    } finally {
        const endTime = performance.now();
        measureApiPerformance(startTime, endTime);
    }
};

/**
 * 매장 목록 로드 함수
 */
async function loadStores(companyId) {
    if (!companyId) {
        console.warn('Cannot load store list without company ID.');
        return;
    }
    
    try {
        console.log(`🏢 Loading store list: ${companyId}`);
        
        const response = await fetch(`api_optimized.php?action=get_stores&company_id=${companyId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const stores = result.data.stores || [];
            const storeSelect = document.getElementById('storeFilter');
            
            // 기존 옵션 삭제 후 기본 옵션 추가
            storeSelect.innerHTML = '<option value="">All Stores</option>';
            
            // 매장 옵션 추가
            stores.forEach(store => {
                const option = document.createElement('option');
                option.value = store.store_id;
                option.textContent = store.store_name; // store_name만 표시
                
                // 비활성 매장은 시각적으로 구분
                if (!store.is_active) {
                    option.textContent += ' [Inactive]';
                    option.style.color = '#6c757d';
                }
                
                storeSelect.appendChild(option);
            });
            
            console.log(`✅ Store list loaded: ${stores.length} stores (active: ${result.data.active_stores}) - showing store names only`);
            
            // 선택된 매장 이름 업데이트
            updateStoreInfo(result.data.company_info.company_name, stores.length, result.data.active_stores);
            
        } else {
            console.error('Failed to load store list:', result.error?.message || 'Unknown error');
            showError(`Failed to load store list: ${result.error?.message || 'Unknown error'}`);
        }
        
    } catch (error) {
        console.error('Error loading store list:', error);
        showError('Network error occurred while loading store list.');
    }
}

/**
 * 매장 정보 업데이트
 */
function updateStoreInfo(companyName, totalStores, activeStores) {
    // 회사 이름 업데이트
    const companyNameElements = document.querySelectorAll('[data-company-name]');
    companyNameElements.forEach(element => {
        element.textContent = companyName;
    });
    
    // 매장 수 정보 업데이트 (옵션)
    const storeInfoElement = document.querySelector('[data-store-info]');
    if (storeInfoElement) {
        storeInfoElement.textContent = `Total ${totalStores} stores (Active: ${activeStores})`;
    }
}



/**
 * 성공 메시지 표시
 */
function showMessage(message, duration = 3000) {
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

console.log('✅ Balance Sheet Frontend 초기화 완료');
console.log('🔧 사용 가능한 디버그 명령어: window.balanceSheetDebug');
console.log('⌨️  키보드 단축키: Ctrl+R(새로고침), F12(디버그)');
console.log('🏢 매장 로드 기능 추가됨');
console.log('📈 차트 및 KPI 시각화 기능 추가됨');
console.log('📅 이미지와 같은 멸진 달력 UI 기능 추가됨');

// ===== 날짜 드롭다운 선택기 기능 =====

/**
 * 날짜 선택기 초기화 (이미지와 같은 달력 UI)
 */
function initializeDateSelectors() {
    console.log('✅ Initializing enhanced calendar UI...');
    
    const periodSelector = document.getElementById('periodSelector');
    const calendarModal = document.getElementById('calendarModal');
    
    if (!periodSelector || !calendarModal) {
        console.error('Calendar elements not found');
        return;
    }
    
    // 초기 표시 설정
    updatePeriodDisplay();
    
    // 이벤트 리스너 설정
    setupCalendarEvents();
    
    // 달력 그리드 렌더링 (만 모달은 숨김 상태로 유지)
    renderCalendarGrid();
    
    // 모달을 명시적으로 숨김 상태로 설정
    const modal = document.getElementById('calendarModal');
    modal.classList.remove('show');
    
    console.log('✅ Enhanced calendar UI initialized successfully');
}

/**
 * 달력 이벤트 리스너 설정
 */
function setupCalendarEvents() {
    const periodSelector = document.getElementById('periodSelector');
    const calendarModal = document.getElementById('calendarModal');
    const closeBtn = document.getElementById('closeCalendarModal');
    const prevBtn = document.getElementById('prevYearRange');
    const nextBtn = document.getElementById('nextYearRange');
    
    // 기간 선택기 클릭 이벤트
    periodSelector.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('📅 Period selector clicked');
        showCalendarModal();
    });
    
    // 닫기 버튼
    closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        hideCalendarModal();
    });
    
    // 연도 네비게이션
    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        changeYearRange(-5);
    });
    
    nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        changeYearRange(5);
    });
    
    // 바깥 클릭시 닫기
    document.addEventListener('click', function(e) {
        if (!calendarModal.contains(e.target) && !periodSelector.contains(e.target)) {
            hideCalendarModal();
        }
    });
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && calendarModal.classList.contains('show')) {
            hideCalendarModal();
        }
    });
}



/**
 * 연도 범위 변경
 */
let calendarStartYear = new Date().getFullYear() - 2;

function changeYearRange(offset) {
    calendarStartYear += offset;
    renderCalendarGrid();
    updateYearRangeDisplay();
}

/**
 * 연도 범위 표시 업데이트
 */
function updateYearRangeDisplay() {
    const yearRangeDisplay = document.getElementById('yearRangeDisplay');
    if (yearRangeDisplay) {
        yearRangeDisplay.textContent = `${calendarStartYear} - ${calendarStartYear + 4}`;
    }
}

/**
 * 달력 그리드 렌더링
 */
function renderCalendarGrid() {
    const yearsGrid = document.getElementById('yearsGrid');
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1;
    
    let html = '';
    
    // 5년 범위 생성
    for (let year = calendarStartYear; year < calendarStartYear + 5; year++) {
        html += `
            <div class="year-section">
                <div class="year-header">${year}</div>
                <div class="months-grid">
        `;
        
        // 12개월 생성
        const monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        
        monthNames.forEach((monthName, index) => {
            const monthNumber = index + 1;
            const isSelected = year === selectedYear && monthNumber === selectedMonth;
            const isCurrent = year === currentYear && monthNumber === currentMonth;
            
            let classes = 'month-btn';
            if (isSelected) classes += ' selected';
            if (isCurrent && !isSelected) classes += ' current';
            
            html += `
                <button type="button" 
                        class="${classes}" 
                        data-year="${year}" 
                        data-month="${monthNumber}">
                    ${monthName}
                </button>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    yearsGrid.innerHTML = html;
    
    // 월 버튼 클릭 이벤트 추가
    yearsGrid.querySelectorAll('.month-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const year = parseInt(this.dataset.year);
            const month = parseInt(this.dataset.month);
            console.log(`📅 Month button clicked: ${year}-${month}`);
            selectPeriod(year, month);
        });
    });
}

/**
 * 기간 선택
 */
function selectPeriod(year, month) {
    console.log(`📅 Period selected: ${year}-${month.toString().padStart(2, '0')}`);
    
    selectedYear = year;
    selectedMonth = month;
    
    updatePeriodDisplay();
    hideCalendarModal();
    
    // 데이터 자동 로드
    loadBalanceSheet();
}

/**
 * 날짜 선택 함수 (달력에서 사용)
 */
function selectDate(year, month) {
    console.log(`📅 Date selected: ${year}-${month.toString().padStart(2, '0')}`);
    
    // 선택된 월의 첫 날과 마지막 날 설정
    selectedStartDate = new Date(year, month - 1, 1);
    selectedEndDate = new Date(year, month, 0); // 다음 달 0일 = 이번 달 마지막 날
    
    // 날짜 표시 업데이트
    updateDatePickerDisplay();
    
    // 달력 숨기기
    const calendarDropdown = document.getElementById('calendarDropdown');
    if (calendarDropdown) {
        calendarDropdown.classList.remove('show');
    }
    
    console.log(`📅 Date range set: ${formatDate(selectedStartDate)} to ${formatDate(selectedEndDate)}`);
    
    // 데이터 자동 로드
    loadBalanceSheet();
}

/**
 * 기간 표시 업데이트
 */
function updatePeriodDisplay() {
    const periodSelector = document.getElementById('periodSelector');
    if (periodSelector) {
        periodSelector.value = `${months[selectedMonth]} ${selectedYear}`;
    }
}

/**
 * 선택된 날짜 가져오기
 */
function getSelectedDate() {
    return {
        year: selectedYear,
        month: selectedMonth
    };
}

/**
 * 날짜 표시 업데이트 (호환성을 위해 유지)
 */
function updateDatePickerDisplay() {
    updatePeriodDisplay();
}

/**
 * 차트 및 KPI 업데이트 함수
 */
function updateChartsAndKPIs() {
    if (!currentData) {
        console.warn('Cannot update charts without current data.');
        return;
    }
    
    try {
        console.log('📈 Starting chart and KPI update...');
        
        // Chart.js 라이브러리 로드 확인
        if (typeof Chart !== 'undefined') {
            // 1. 자산 구성 차트 업데이트
            updateAssetsChart();
            
            // 2. 부채 vs 자본 차트 업데이트
            updateLiabilitiesEquityChart();
        } else {
            console.warn('Chart.js library not loaded. Skipping chart updates.');
            // 차트 캔버스에 텍스트 표시
            displayChartPlaceholder();
        }
        
        // 3. KPI 지표 업데이트 (Chart.js 없이도 가능)
        updateKPIIndicators();
        
        console.log('✅ Chart and KPI update completed');
        
    } catch (error) {
        console.error('Error updating charts:', error);
    }
}

/**
 * 차트 플레이스홀더 표시 함수
 */
function displayChartPlaceholder() {
    const assetsCanvas = document.getElementById('assetsChart');
    const liabEquityCanvas = document.getElementById('liabEquityChart');
    
    [assetsCanvas, liabEquityCanvas].forEach(canvas => {
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = '#6c757d';
            ctx.font = '14px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Charts require Chart.js library', canvas.width / 2, canvas.height / 2);
        }
    });
}

/**
 * 자산 구성 도너츠 차트
 */
let assetsChart = null;
function updateAssetsChart() {
    const canvas = document.getElementById('assetsChart');
    if (!canvas || typeof Chart === 'undefined') return;
    
    const ctx = canvas.getContext('2d');
    
    // 기존 차트 제거
    if (assetsChart) {
        assetsChart.destroy();
    }
    
    // 데이터 준비
    const currentAssets = currentData.assets.total_current_assets || 0;
    const nonCurrentAssets = currentData.assets.total_non_current_assets || 0;
    
    if (currentAssets === 0 && nonCurrentAssets === 0) {
        // 데이터가 없을 때 대체 표시
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#6c757d';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // 차트 생성
    assetsChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Current Assets', 'Non-Current Assets'],
            datasets: [{
                data: [currentAssets, nonCurrentAssets],
                backgroundColor: [
                    '#0891b2', // 유동자산 - 밝은 파란
                    '#0e7490'  // 비유동자산 - 진한 파란
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            const formattedValue = new Intl.NumberFormat('en-US').format(value);
                            return `${label}: ${formattedValue} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * 부채 vs 자본 차트
 */
let liabEquityChart = null;
function updateLiabilitiesEquityChart() {
    const canvas = document.getElementById('liabEquityChart');
    if (!canvas || typeof Chart === 'undefined') return;
    
    const ctx = canvas.getContext('2d');
    
    // 기존 차트 제거
    if (liabEquityChart) {
        liabEquityChart.destroy();
    }
    
    // 데이터 준비
    const totalLiabilities = currentData.liabilities.total_liabilities || 0;
    const totalEquity = currentData.equity.total_equity || 0;
    
    if (totalLiabilities === 0 && totalEquity === 0) {
        // 데이터가 없을 때 대체 표시
        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#6c757d';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }
    
    // 차트 생성
    liabEquityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Liabilities', 'Equity'],
            datasets: [{
                label: 'Amount',
                data: [totalLiabilities, totalEquity],
                backgroundColor: [
                    '#d97706', // 부채 - 주황색
                    '#059669'  // 자본 - 녹색
                ],
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('en-US', {
                                notation: 'compact',
                                maximumFractionDigits: 1
                            }).format(value);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed.y;
                            const formattedValue = new Intl.NumberFormat('en-US').format(value);
                            return `${context.label}: ${formattedValue}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * KPI 지표 업데이트
 */
function updateKPIIndicators() {
    const totalAssets = currentData.totals.total_assets || 0;
    const totalLiabilities = currentData.liabilities.total_liabilities || 0;
    const totalEquity = currentData.equity.total_equity || 0;
    const currentAssets = currentData.assets.total_current_assets || 0;
    const currentLiabilities = currentData.liabilities.total_current_liabilities || 0;
    
    // 1. 부채비율 (Debt Ratio) = 부채 / 자산
    const debtRatio = totalAssets > 0 ? (totalLiabilities / totalAssets) * 100 : 0;
    updateKPICard('debtRatio', debtRatio, '%', 'debtRatioBar');
    
    // 2. 자기자본비율 (Equity Ratio) = 자본 / 자산
    const equityRatio = totalAssets > 0 ? (totalEquity / totalAssets) * 100 : 0;
    updateKPICard('equityRatio', equityRatio, '%', 'equityRatioBar');
    
    // 3. 유동비율 (Current Ratio) = 유동자산 / 유동부채
    const liquidityRatio = currentLiabilities > 0 ? (currentAssets / currentLiabilities) * 100 : 0;
    updateKPICard('liquidityRatio', liquidityRatio, '%', 'liquidityRatioBar');
    
    // 4. 총자산 규모
    const currencySymbol = currentData.company_info.currency_symbol || '₫';
    const formattedAssets = formatCurrency(totalAssets, currencySymbol);
    document.getElementById('totalAssetsKpi').textContent = formattedAssets;
    
    // 자산 변화율 (샘플 데이터)
    const assetsChangePercent = 2.3; // 샘플 데이터
    const changeElement = document.getElementById('assetsChange');
    changeElement.textContent = `+${assetsChangePercent}%`;
    changeElement.className = assetsChangePercent >= 0 ? 'kpi-change' : 'kpi-change negative';
}

/**
 * KPI 카드 업데이트 헬퍼 함수
 */
function updateKPICard(valueId, value, suffix, progressBarId) {
    // 값 업데이트
    const valueElement = document.getElementById(valueId);
    if (valueElement) {
        valueElement.textContent = `${value.toFixed(1)}${suffix}`;
    }
    
    // 프로그레스 바 업데이트
    const progressBar = document.getElementById(progressBarId);
    if (progressBar) {
        const percentage = Math.min(Math.max(value, 0), 100); // 0-100% 단위로 제한
        progressBar.style.width = `${percentage}%`;
        
        // 색상 조정 (비율에 따라)
        if (valueId === 'debtRatio') {
            // 부채비율: 30% 이하 우수, 30-60% 보통, 60% 이상 주의
            if (value <= 30) {
                progressBar.className = 'progress-bar bg-success';
            } else if (value <= 60) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-danger';
            }
        } else if (valueId === 'equityRatio') {
            // 자기자본비율: 40% 이상 우수, 20-40% 보통, 20% 이하 주의
            if (value >= 40) {
                progressBar.className = 'progress-bar bg-success';
            } else if (value >= 20) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-danger';
            }
        } else if (valueId === 'liquidityRatio') {
            // 유동비율: 200% 이상 우수, 100-200% 보통, 100% 이하 주의
            if (value >= 200) {
                progressBar.className = 'progress-bar bg-success';
            } else if (value >= 100) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-danger';
            }
        }
    }
}

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
    
    // Reposition calendar on window resize
    window.addEventListener('resize', function() {
        if (calendarDropdown.classList.contains('show')) {
            positionCalendar(calendarDropdown, datePicker);
        }
    });
    
    // Navigation buttons
    const prevYear = document.getElementById('prevYear');
    const nextYear = document.getElementById('nextYear');
    const closeCalendar = document.getElementById('closeCalendar');
    
    if (prevYear) {
        prevYear.addEventListener('click', function() {
            calendarStartYear--;
            renderYearMonthGrid();
            updateYearDisplay();
        });
    }
    
    if (nextYear) {
        nextYear.addEventListener('click', function() {
            calendarStartYear++;
            renderYearMonthGrid();
            updateYearDisplay();
        });
    }
    
    if (closeCalendar) {
        closeCalendar.addEventListener('click', function() {
            hideCalendar();
        });
    }
    
    // ESC key to close calendar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && calendarDropdown.classList.contains('show')) {
            hideCalendar();
        }
    });
    
    renderYearMonthGrid();
    updateDatePickerDisplay();
}

function showCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    const datePicker = document.getElementById('datePicker');
    
    if (!calendarDropdown || !datePicker) return;
    
    // Position calendar optimally
    positionCalendar(calendarDropdown, datePicker);
    
    calendarDropdown.classList.add('show');
    renderYearMonthGrid(); // Refresh the grid when showing
}



function positionCalendar(dropdown, input) {
    const rect = input.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const viewportWidth = window.innerWidth;
    const dropdownHeight = 400; // Estimated height
    
    // Reset positioning
    dropdown.style.position = 'absolute';
    dropdown.style.top = '100%';
    dropdown.style.left = '0';
    dropdown.style.right = '0';
    dropdown.style.bottom = 'auto';
    
    // Check if dropdown would go off-screen vertically
    if (rect.bottom + dropdownHeight > viewportHeight) {
        // Position above the input if there's more space
        if (rect.top > viewportHeight - rect.bottom) {
            dropdown.style.top = 'auto';
            dropdown.style.bottom = '100%';
            dropdown.style.marginTop = '0';
            dropdown.style.marginBottom = '0.5rem';
        } else {
            // Keep below but adjust margin
            dropdown.style.marginTop = '0.25rem';
        }
    }
    
    // On mobile, use fixed positioning for better control
    if (viewportWidth <= 768) {
        dropdown.style.position = 'fixed';
        dropdown.style.left = '20px';
        dropdown.style.right = '20px';
        dropdown.style.top = '50%';
        dropdown.style.bottom = 'auto';
        dropdown.style.transform = 'translateY(-50%)';
        dropdown.style.margin = '0';
    }
}

function hideCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    if (calendarDropdown) {
        calendarDropdown.classList.remove('show');
    }
}

function renderYearMonthGrid() {
    const grid = document.getElementById('yearMonthGrid');
    if (!grid) return;
    
    const currentDate = new Date();
    const currentYear = currentDate.getFullYear();
    const currentMonth = currentDate.getMonth() + 1;
    
    let html = '';
    
    // Generate 5 years
    for (let year = calendarStartYear; year < calendarStartYear + 5; year++) {
        html += `
            <div class="year-section">
                <div class="year-header">${year}</div>
                <div class="months-grid">
        `;
        
        // Generate 12 months for each year
        const monthsShort = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        
        monthsShort.forEach((monthName, index) => {
            const monthNumber = index + 1;
            const isSelected = year === selectedYear && monthNumber === selectedMonth;
            const isCurrent = year === currentYear && monthNumber === currentMonth;
            
            let classes = 'month-btn';
            if (isSelected) classes += ' selected';
            if (isCurrent && !isSelected) classes += ' current';
            
            html += `
                <button type="button" 
                        class="${classes}" 
                        data-year="${year}" 
                        data-month="${monthNumber}">
                    ${monthName}
                </button>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
    }
    
    grid.innerHTML = html;
    
    // Add click listeners to month buttons
    grid.querySelectorAll('.month-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const year = parseInt(this.dataset.year);
            const month = parseInt(this.dataset.month);
            
            selectDate(year, month);
        });
    });
    
    updateYearDisplay();
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
    
    // 기존 이벤트 리스너 제거 (중복 방지)
    datePicker.removeEventListener('click', datePicker.clickHandler);
    
    // 새로운 이벤트 리스너 등록 - 더 정확한 상태 체크
    datePicker.clickHandler = function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('📅 Date picker clicked!');
        
        // 현재 보이는지 확인 - computed style로 더 정확하게 확인
        const computedStyle = window.getComputedStyle(calendarDropdown);
        const isVisible = computedStyle.display !== 'none' && calendarDropdown.classList.contains('show');
        
        console.log('  Calendar current state:');
        console.log('    - has show class:', calendarDropdown.classList.contains('show'));
        console.log('    - computed display:', computedStyle.display);
        console.log('    - isVisible:', isVisible);
        
        if (isVisible) {
            console.log('  Hiding calendar...');
            calendarDropdown.classList.remove('show');
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
    
    console.log('📅 Date range picker initialized successfully');
}

// 날짜 범위 달력 표시
function showDateRangeCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    if (!calendarDropdown) return;
    
    calendarDropdown.classList.add('show');
    renderDateRangeCalendar();
    
    // 위치 조정
    const datePicker = document.getElementById('datePicker');
    if (datePicker) {
        positionCalendar(calendarDropdown, datePicker);
    }
}

// 날짜 범위 달력 렌더링
function renderDateRangeCalendar() {
    const calendarDropdown = document.getElementById('calendarDropdown');
    if (!calendarDropdown) return;
    
    const today = new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth();
    
    calendarDropdown.innerHTML = `
        <div class="calendar-header">
            <button type="button" class="calendar-nav-btn" id="prevMonth">
                <i class="bi bi-chevron-left"></i>
            </button>
            <span id="currentMonthDisplay" class="month-display">${months[currentMonth + 1]} ${currentYear}</span>
            <button type="button" class="calendar-nav-btn" id="nextMonth">
                <i class="bi bi-chevron-right"></i>
            </button>
            <button type="button" class="calendar-close-btn" id="closeCalendar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="date-range-info">
            <div class="range-input-group">
                <label>Start Date:</label>
                <input type="date" id="startDateInput" value="${formatDate(selectedStartDate)}">
            </div>
            <div class="range-input-group">
                <label>End Date:</label>
                <input type="date" id="endDateInput" value="${formatDate(selectedEndDate)}">
            </div>
        </div>
        <div class="calendar-body" id="calendarBody">
            ${renderCalendarDays(currentYear, currentMonth)}
        </div>
        <div class="calendar-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="setDefaultDateRange()">This Month</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setLastMonthRange()">Last Month</button>
            <button type="button" class="btn btn-primary btn-sm" onclick="applyDateRange()">Apply</button>
        </div>
    `;
    
    // 이벤트 리스너 추가
    setupDateRangeEvents();
}

// 달력 날짜 렌더링
function renderCalendarDays(year, month) {
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startWeek = new Date(firstDay);
    startWeek.setDate(firstDay.getDate() - firstDay.getDay());
    
    let html = '<table class="calendar-table"><thead><tr>';
    const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weekDays.forEach(day => {
        html += `<th>${day}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    let currentDate = new Date(startWeek);
    for (let week = 0; week < 6; week++) {
        html += '<tr>';
        for (let day = 0; day < 7; day++) {
            const isCurrentMonth = currentDate.getMonth() === month;
            const isSelected = isDateInRange(currentDate, selectedStartDate, selectedEndDate);
            const isTodayCheck = isTodayDate(currentDate);
            
            let classes = 'calendar-day';
            if (!isCurrentMonth) classes += ' other-month';
            if (isSelected) classes += ' selected';
            if (isTodayCheck) classes += ' today';
            
            html += `<td><button type="button" class="${classes}" data-date="${formatDate(currentDate)}">${currentDate.getDate()}</button></td>`;
            currentDate.setDate(currentDate.getDate() + 1);
        }
        html += '</tr>';
        
        if (currentDate.getMonth() !== month && week >= 4) break;
    }
    
    html += '</tbody></table>';
    return html;
}

// 날짜가 범위 내에 있는지 확인
function isDateInRange(date, startDate, endDate) {
    if (!startDate || !endDate) return false;
    return date >= startDate && date <= endDate;
}

// 오늘 날짜인지 확인
function isTodayDate(date) {
    const today = new Date();
    return date.toDateString() === today.toDateString();
}

// 지난 달 범위 설정
function setLastMonthRange() {
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    selectedStartDate = lastMonth;
    selectedEndDate = new Date(today.getFullYear(), today.getMonth(), 0);
    
    updateDateInputs();
    updateDatePickerDisplay();
}

// 날짜 입력 필드 업데이트
function updateDateInputs() {
    const startInput = document.getElementById('startDateInput');
    const endInput = document.getElementById('endDateInput');
    
    if (startInput) startInput.value = formatDate(selectedStartDate);
    if (endInput) endInput.value = formatDate(selectedEndDate);
}

// 날짜 범위 적용
function applyDateRange() {
    const startInput = document.getElementById('startDateInput');
    const endInput = document.getElementById('endDateInput');
    
    if (startInput && endInput) {
        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);
        
        if (startDate <= endDate) {
            selectedStartDate = startDate;
            selectedEndDate = endDate;
            updateDatePickerDisplay();
            hideCalendar();
            
            console.log('📅 Date range applied:', {
                start: formatDate(selectedStartDate),
                end: formatDate(selectedEndDate)
            });
            
            // Auto-load new data
            // loadBalanceSheet(); // 나중에 연결
        } else {
            alert('Start date must be before end date.');
        }
    }
}

// 날짜 범위 이벤트 설정
function setupDateRangeEvents() {
    // 닫기 버튼
    const closeBtn = document.getElementById('closeCalendar');
    if (closeBtn) {
        closeBtn.addEventListener('click', hideCalendar);
    }
    
    // 날짜 입력 필드 변경 이벤트
    const startInput = document.getElementById('startDateInput');
    const endInput = document.getElementById('endDateInput');
    
    if (startInput) {
        startInput.addEventListener('change', function() {
            const newDate = new Date(this.value);
            if (newDate <= selectedEndDate) {
                selectedStartDate = newDate;
                renderDateRangeCalendar();
            }
        });
    }
    
    if (endInput) {
        endInput.addEventListener('change', function() {
            const newDate = new Date(this.value);
            if (newDate >= selectedStartDate) {
                selectedEndDate = newDate;
                renderDateRangeCalendar();
            }
        });
    }
    
    // 달력 날짜 클릭 이벤트
    const calendarBody = document.getElementById('calendarBody');
    if (calendarBody) {
        calendarBody.addEventListener('click', function(e) {
            const dayBtn = e.target.closest('.calendar-day');
            if (dayBtn && !dayBtn.classList.contains('other-month')) {
                const clickedDate = new Date(dayBtn.dataset.date);
                
                // 시작일과 종료일 설정 로직
                if (!selectedStartDate || clickedDate < selectedStartDate || 
                    (selectedStartDate && selectedEndDate && clickedDate !== selectedStartDate && clickedDate !== selectedEndDate)) {
                    selectedStartDate = clickedDate;
                    selectedEndDate = clickedDate;
                } else if (clickedDate >= selectedStartDate) {
                    selectedEndDate = clickedDate;
                }
                
                updateDateInputs();
                renderDateRangeCalendar();
            }
        });
    }
}

function updateYearDisplay() {
    const display = document.getElementById('currentYearDisplay');
    if (display) {
        display.textContent = `${calendarStartYear} - ${calendarStartYear + 4}`;
    }
}

/**
 * 통화 포맷팅 헬퍼 함수
 */
function formatCurrency(amount, symbol = '₫') {
    if (amount === 0) return `${symbol}0`;
    
    const absAmount = Math.abs(amount);
    let formatted;
    
    if (absAmount >= 1e12) {
        formatted = `${(absAmount / 1e12).toFixed(1)}T`;
    } else if (absAmount >= 1e9) {
        formatted = `${(absAmount / 1e9).toFixed(1)}B`;
    } else if (absAmount >= 1e6) {
        formatted = `${(absAmount / 1e6).toFixed(1)}M`;
    } else if (absAmount >= 1e3) {
        formatted = `${(absAmount / 1e3).toFixed(1)}K`;
    } else {
        formatted = new Intl.NumberFormat('en-US').format(absAmount);
    }
    
    return `${symbol}${amount < 0 ? '-' : ''}${formatted}`;
}

// Change company function - now uses navigation enhancement
function changeCompany(companyId) {
    if (companyId && companyId !== params.company_id) {
        // Update navigation state for dynamic linking
        if (window.updateNavigationCompany) {
            window.updateNavigationCompany(companyId);
        }
        
        // Update URL and reload (let dashboard handle the data)
        const newUrl = `?user_id=${params.user_id}&company_id=${companyId}`;
        
        console.log('Company changed to:', companyId);
        window.location.href = newUrl;
    }
}

// Initialize page on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Balance Sheet page initialized with params:', params);
    
    // DO NOT load user companies - get from page state via navigation
    // loadUserCompaniesAndStores(); // REMOVED: Should only be called from Dashboard
    
    // Initialize NEW date range picker
    initializeDateRangePicker();
    
    // Load initial balance sheet data
    if (typeof loadBalanceSheetData === 'function') {
        loadBalanceSheetData();
    } else if (typeof loadBalanceSheet === 'function') {
        loadBalanceSheet();
    }
    
    console.log('Balance Sheet initialization complete (no RPC calls)');
});
