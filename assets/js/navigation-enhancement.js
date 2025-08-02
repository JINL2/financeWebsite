/**
 * Navigation Enhancement Script
 * 모든 페이지의 네비게이션 링크가 확실히 작동하도록 보장
 * 동적 파라미터 업데이트 지원
 */

// 전역 네비게이션 상태 관리
window.NavigationState = {
    user_id: null,
    company_id: null,
    store_id: null,
    
    // 사용자 회사/스토어 데이터 (대시보드에서 설정됨)
    userCompaniesData: null,
    
    // 상태 초기화
    init: function() {
        const urlParams = new URLSearchParams(window.location.search);
        this.user_id = urlParams.get('user_id');
        this.company_id = urlParams.get('company_id');
        this.store_id = urlParams.get('store_id');
        
        if (!this.user_id || !this.company_id) {
            console.warn('Missing required parameters, redirecting to login');
            window.location.href = '../login/';
            return false;
        }
        
        console.log('Navigation state initialized:', {
            user_id: this.user_id,
            company_id: this.company_id,
            store_id: this.store_id
        });
        
        // SessionStorage에서 사용자 데이터 자동 로드
        this.loadUserDataFromStorage();
        
        return true;
    },
    
    // 사용자 데이터 설정 (대시보드에서만 호출)
    setUserData: function(userData) {
        this.userCompaniesData = userData;
        // SessionStorage에도 저장
        sessionStorage.setItem('userCompaniesData', JSON.stringify(userData));
        console.log('User companies data set in NavigationState:', userData);
    },
    
    // SessionStorage에서 사용자 데이터 로드
    loadUserDataFromStorage: function() {
        try {
            const storedData = sessionStorage.getItem('userCompaniesData');
            if (storedData) {
                this.userCompaniesData = JSON.parse(storedData);
                console.log('📱 User companies data loaded from SessionStorage:', this.userCompaniesData);
                return true;
            }
        } catch (error) {
            console.error('Error loading data from SessionStorage:', error);
        }
        return false;
    },
    
    // 현재 회사의 스토어 목록 가져오기
    getCurrentCompanyStores: function() {
        if (!this.userCompaniesData || !this.userCompaniesData.companies) {
            return [];
        }
        
        const currentCompany = this.userCompaniesData.companies.find(
            company => company.company_id === this.company_id
        );
        
        return currentCompany ? currentCompany.stores : [];
    },
    
    // 회사 변경
    setCompany: function(companyId) {
        if (companyId && companyId !== this.company_id) {
            this.company_id = companyId;
            this.store_id = null; // 회사 변경 시 스토어 초기화
            this.updateAllLinks();
            this.updateNavbarCompanyDropdown(); // 네비게이션 바 드롭다운도 업데이트
            console.log('Company changed to:', companyId);
        }
    },
    
    // 스토어 변경
    setStore: function(storeId) {
        this.store_id = storeId;
        this.updateAllLinks();
        console.log('Store changed to:', storeId);
    },
    
    // 현재 파라미터 문자열 생성
    getParams: function() {
        const baseParams = `user_id=${this.user_id}&company_id=${this.company_id}`;
        return this.store_id ? `${baseParams}&store_id=${this.store_id}` : baseParams;
    },
    
    // 모든 네비게이션 링크 업데이트
    updateAllLinks: function() {
        const currentParams = this.getParams();
        
        // 네비게이션 바 링크들
        this.updateNavbarLinks(currentParams);
        
        // 퀵 액션 링크들
        this.updateQuickActionLinks(currentParams);
        
        // 드롭다운 링크들
        this.updateDropdownLinks(currentParams);
        
        // View All 링크들
        this.updateViewAllLinks(currentParams);
        
        console.log('All navigation links updated with params:', currentParams);
    },
    
    // 네비게이션 바 Company 드롭다운 업데이트
    updateNavbarCompanyDropdown: function() {
        const companySelect = document.querySelector('nav select[id*="company"], nav .company-select, nav select');
        if (!companySelect) {
            console.log('🚫 No company dropdown found in navbar');
            return;
        }
        
        // SessionStorage에서 데이터 로드
        let companiesData = null;
        try {
            const storedData = sessionStorage.getItem('userCompaniesData');
            if (storedData) {
                const userData = JSON.parse(storedData);
                companiesData = userData.companies;
            }
        } catch (error) {
            console.error('Error loading companies from SessionStorage:', error);
        }
        
        // 데이터가 없으면 기본 상태 유지
        if (!companiesData || !Array.isArray(companiesData)) {
            console.log('🚫 No companies data available for navbar dropdown');
            return;
        }
        
        // 드롭다운 업데이트
        companySelect.innerHTML = '';
        
        companiesData.forEach(company => {
            const option = document.createElement('option');
            option.value = company.company_id;
            option.textContent = company.company_name;
            option.selected = company.company_id === this.company_id;
            companySelect.appendChild(option);
        });
        
        // 변경 이벤트 리스너 추가
        companySelect.removeEventListener('change', this.handleNavbarCompanyChange);
        companySelect.addEventListener('change', this.handleNavbarCompanyChange.bind(this));
        
        console.log('🏢 Navbar company dropdown updated with', companiesData.length, 'companies');
    },
    
    // 네비게이션 바 Company 변경 처리
    handleNavbarCompanyChange: function(event) {
        const newCompanyId = event.target.value;
        if (newCompanyId && newCompanyId !== this.company_id) {
            console.log('🔄 Company changed in navbar to:', newCompanyId);
            this.setCompany(newCompanyId);
            
            // URL 업데이트 및 페이지 이동
            const newUrl = `${window.location.pathname}?${this.getParams()}`;
            window.location.href = newUrl;
        }
    },
    
    // 네비게이션 바 링크 업데이트
    updateNavbarLinks: function(params) {
        const navLinks = document.querySelectorAll('.navbar-nav a[href^="../"]');
        navLinks.forEach(link => {
            const basePath = link.getAttribute('href').split('?')[0];
            link.href = `${basePath}?${params}`;
        });
    },
    
    // 퀵 액션 링크 업데이트
    updateQuickActionLinks: function(params) {
        const quickActionLinks = document.querySelectorAll('.action-btn[href^="../"]');
        quickActionLinks.forEach(link => {
            const originalHref = link.getAttribute('href');
            const basePath = originalHref.split('?')[0];
            const urlParams = new URLSearchParams(originalHref.split('?')[1] || '');
            
            // 기존 type 등의 추가 파라미터 유지
            const additionalParams = [];
            for (const [key, value] of urlParams.entries()) {
                if (!['user_id', 'company_id', 'store_id'].includes(key)) {
                    additionalParams.push(`${key}=${value}`);
                }
            }
            
            const finalParams = additionalParams.length > 0 
                ? `${params}&${additionalParams.join('&')}`
                : params;
            
            link.href = `${basePath}?${finalParams}`;
        });
    },
    
    // 드롭다운 링크 업데이트
    updateDropdownLinks: function(params) {
        const dropdownLinks = document.querySelectorAll('.dropdown-menu a[href^="../"]');
        dropdownLinks.forEach(link => {
            const basePath = link.getAttribute('href').split('?')[0];
            link.href = `${basePath}?${params}`;
        });
    },
    
    // View All 링크 업데이트
    updateViewAllLinks: function(params) {
        const viewAllLinks = document.querySelectorAll('.view-all-btn[href^="../"]');
        viewAllLinks.forEach(link => {
            const basePath = link.getAttribute('href').split('?')[0];
            link.href = `${basePath}?${params}`;
        });
    },
    
    // 페이지 이동
    navigateTo: function(page, additionalParams = '') {
        const params = additionalParams ? `${this.getParams()}&${additionalParams}` : this.getParams();
        const url = `../${page}/?${params}`;
        console.log('Navigating to:', url);
        window.location.href = url;
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('Navigation Enhancement Script loaded');
    
    // 네비게이션 상태 초기화
    if (!window.NavigationState.init()) {
        return;
    }
    
    // 네비게이션 바 Company 드롭다운 업데이트
    window.NavigationState.updateNavbarCompanyDropdown();
    
    // 초기 링크 업데이트
    window.NavigationState.updateAllLinks();
    
    // 클릭 이벤트 리스너 추가 (모든 네비게이션 링크)
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href^="../"]');
        if (link) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetHref = link.href;
            console.log('Navigation click:', targetHref);
            
            // 강제로 페이지 이동
            window.location.href = targetHref;
        }
    });
    
    console.log('Navigation enhancement complete');
    
    // 전역 함수 별칭
    window.navigateTo = function(page, additionalParams = '') {
        return window.NavigationState.navigateTo(page, additionalParams);
    };
    
    // 디버그 함수
    window.debugNavigation = function() {
        return {
            state: window.NavigationState,
            params: window.NavigationState.getParams(),
            navLinksCount: document.querySelectorAll('.navbar-nav a[href^="../"]').length,
            quickActionLinksCount: document.querySelectorAll('.action-btn[href^="../"]').length,
            dropdownLinksCount: document.querySelectorAll('.dropdown-menu a[href^="../"]').length
        };
    };
});

/**
 * 백업 함수들 - 필요시 수동으로 호출 가능
 */
window.forceNavigateTo = function(page, params = '') {
    return window.NavigationState.navigateTo(page, params);
};

window.fixAllNavigation = function() {
    window.NavigationState.updateAllLinks();
    console.log('All navigation links fixed');
};

// 회사 변경 함수 (대시보드에서 사용)
window.updateNavigationCompany = function(companyId) {
    window.NavigationState.setCompany(companyId);
};

// 스토어 변경 함수 (필터링에서 사용)
window.updateNavigationStore = function(storeId) {
    window.NavigationState.setStore(storeId);
};
