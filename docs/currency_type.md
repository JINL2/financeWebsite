# Currency Type System Documentation

## Overview
Finance Management System에서 통화 표시를 일관되게 처리하는 방법에 대한 문서입니다.

## Database Structure

### Tables
1. **companies** 테이블
   - `company_id`: 회사 ID
   - `base_currency_id`: 기본 통화 ID (currency_types 테이블 참조)

2. **currency_types** 테이블
   - `currency_id`: 통화 ID
   - `currency_code`: ISO 통화 코드 (예: USD, VND, KRW)
   - `currency_name`: 통화 이름 (예: US Dollar, Vietnamese Dong)
   - `symbol`: 통화 심볼 (예: $, ₫, ₩)

## Implementation

### 1. Common Function (functions.php)
```php
/**
 * 회사의 통화 정보 가져오기
 * @param string $company_id
 * @return array ['currency_code' => 'VND', 'currency_symbol' => '₫', 'currency_name' => 'Vietnamese Dong']
 */
function getCompanyCurrency($company_id) {
    $db = new SupabaseDB();
    
    try {
        $company_info = $db->query('companies', [
            'company_id' => 'eq.' . $company_id,
            'select' => 'company_id,company_name,base_currency_id,currency_types(currency_code,symbol,currency_name)'
        ]);
        
        if (!empty($company_info) && isset($company_info[0]['currency_types'])) {
            return [
                'currency_code' => $company_info[0]['currency_types']['currency_code'] ?? 'VND',
                'currency_symbol' => $company_info[0]['currency_types']['symbol'] ?? '₫',
                'currency_name' => $company_info[0]['currency_types']['currency_name'] ?? 'Vietnamese Dong'
            ];
        }
    } catch (Exception $e) {
        error_log("Currency fetch error: " . $e->getMessage());
    }
    
    // 기본값 반환
    return [
        'currency_code' => 'VND',
        'currency_symbol' => '₫',
        'currency_name' => 'Vietnamese Dong'
    ];
}
```

### 2. Page Implementation Pattern

#### PHP (Backend)
```php
// 모든 페이지에서 통화 정보 가져오기
$currency_info = getCompanyCurrency($company_id);
$currency_code = $currency_info['currency_code'];
$currency_symbol = $currency_info['currency_symbol'];
```

#### JavaScript (Frontend)
```javascript
// 통화 정보를 JavaScript 변수로 전달
let currencyCode = '<?= $currency_code ?>';
let currencySymbol = '<?= $currency_symbol ?>';

// 금액 포맷팅 함수
function formatCurrency(amount) {
    return currencySymbol + numberWithCommas(Math.round(amount));
}
```

### 3. API Response Pattern
모든 API는 통화 정보를 응답에 포함해야 합니다:

```php
echo json_encode([
    'success' => true,
    'data' => $data,
    'currency_symbol' => $currency_symbol,
    'currency_code' => $currency_code
]);
```

### 4. URL Parameter Pattern
페이지 간 이동시 필수 파라미터:
- `user_id`: 사용자 ID
- `company_id`: 회사 ID
- `store_id`: (선택사항) 매장 ID

통화 정보는 company_id를 통해 각 페이지에서 동적으로 조회합니다.

## Usage Examples

### 1. Dashboard to Transaction Page
```php
<a href="../transactions/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">
    View All Transactions
</a>
```

### 2. Quick Action Links
```php
<a href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>&type=income">
    Add Income
</a>
```

### 3. Financial Statements
```php
<a href="../balance-sheet/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">
    Balance Sheet
</a>
```

## Important Notes

1. **절대 하드코딩 금지**: 통화 심볼을 하드코딩하지 않습니다
2. **동적 조회**: 각 페이지에서 company_id를 기반으로 통화 정보를 조회합니다
3. **일관성**: 모든 페이지에서 동일한 함수를 사용하여 통화를 처리합니다
4. **API 표준화**: 모든 API 응답에 통화 정보를 포함합니다
5. **에러 처리**: 통화 정보 조회 실패시 기본값(VND)을 사용합니다

## Benefits

1. **유지보수성**: 한 곳에서 통화 처리 로직 관리
2. **확장성**: 새로운 통화 추가가 용이
3. **일관성**: 시스템 전체에서 동일한 통화 표시
4. **성능**: 필요할 때만 DB 조회, 캐싱 가능
5. **유연성**: 회사별 다른 통화 지원
