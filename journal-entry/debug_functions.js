/**
 * Journal Entry - Debug and Testing Functions
 * Category Tag 기능 테스트를 위한 디버그 함수들 (데모 데이터 제거)
 */

/**
 * 디버그용 계정 타입 테스트 함수
 */
function debugAccountTypes() {
    console.log('=== Account Types Debug ===');
    console.log('Total accounts loaded:', accountsData?.length || 0);
    
    if (accountsData && accountsData.length > 0) {
        const categoryTags = {};
        accountsData.forEach(account => {
            const tag = account.category_tag || 'none';
            if (!categoryTags[tag]) {
                categoryTags[tag] = [];
            }
            categoryTags[tag].push(account.name);
        });
        
        console.log('Category tags found:');
        Object.keys(categoryTags).forEach(tag => {
            console.log(`- ${tag}: ${categoryTags[tag].length} accounts`);
            categoryTags[tag].slice(0, 3).forEach(name => {
                console.log(`  * ${name}`);
            });
        });
    }
    
    return accountsData;
}

/**
 * 특정 category_tag를 가진 계정 필터링 테스트
 */
function testCategoryTag(tag) {
    if (!accountsData) {
        console.error('Account data not loaded yet');
        return [];
    }
    
    const filtered = accountsData.filter(account => account.category_tag === tag);
    console.log(`Accounts with category_tag "${tag}":`, filtered.length);
    filtered.forEach(account => {
        console.log(`- ${account.name} (ID: ${account.id})`);
    });
    
    return filtered;
}

/**
 * UI 상태 디버그 함수
 */
function debugLineStates() {
    const lines = document.querySelectorAll('.journal-line');
    console.log('=== Journal Lines Debug ===');
    console.log(`Total lines: ${lines.length}`);
    
    lines.forEach((line, index) => {
        const accountInput = line.querySelector('.account-search-input');
        const accountId = line.querySelector('.account-id-hidden');
        const cashLocation = line.querySelector('.cash-location-select');
        const counterpartyContainer = line.querySelector('.counterparty-selector-container');
        
        console.log(`Line ${index + 1}:`);
        console.log(`  Account: ${accountInput?.value || 'None'}`);
        console.log(`  Account ID: ${accountId?.value || 'None'}`);
        console.log(`  Cash Location visible: ${cashLocation?.style.display !== 'none'}`);
        console.log(`  Counterparty container: ${counterpartyContainer ? 'Present' : 'Not present'}`);
    });
}

/**
 * Counterparty 데이터 디버깅 함수
 */
function debugCounterparties() {
    console.log('=== Counterparties Debug ===');
    if (window.counterpartiesData) {
        console.log('Counterparties data:', window.counterpartiesData);
        console.log('Internal counterparties:', window.counterpartiesData.filter(cp => cp.is_internal));
    } else {
        console.log('No counterparties data loaded');
    }
}

/**
 * 초기화 후 디버그 실행
 */
document.addEventListener('DOMContentLoaded', function() {
    // 원래 초기화 후 디버그 함수들 실행
    setTimeout(() => {
        console.log('=== Journal Entry Category Tag System Debug ===');
        debugAccountTypes();
        
        // 개발자 콘솔에서 사용할 수 있도록 전역 함수로 노출
        window.debugJournal = {
            debugAccountTypes,
            testCategoryTag,
            debugLineStates,
            debugCounterparties
        };
        
        console.log('Debug functions available:');
        console.log('- window.debugJournal.debugAccountTypes()');
        console.log('- window.debugJournal.testCategoryTag("cash")');
        console.log('- window.debugJournal.debugLineStates()');
        console.log('- window.debugJournal.debugCounterparties()');
    }, 1000);
});
