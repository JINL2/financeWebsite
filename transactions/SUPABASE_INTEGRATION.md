# Transactions Module - Supabase Integration

## Supabase Configuration

### Project Info
- **URL**: https://atkekzwgukdvucqntryo.supabase.co
- **Anon Key**: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8

## Database Tables Used

### 1. journal_entries
전표 헤더 정보
```sql
CREATE TABLE journal_entries (
    journal_id UUID PRIMARY KEY,
    company_id UUID NOT NULL,
    store_id UUID,
    entry_date DATE NOT NULL,
    description TEXT,
    counterparty_id UUID,
    created_by UUID,
    created_at TIMESTAMP,
    currency_id UUID,
    base_amount NUMERIC
);
```

### 2. journal_lines
전표 라인 정보
```sql
CREATE TABLE journal_lines (
    line_id UUID PRIMARY KEY,
    journal_id UUID NOT NULL,
    account_id UUID NOT NULL,
    debit NUMERIC DEFAULT 0,
    credit NUMERIC DEFAULT 0,
    description TEXT,
    store_id UUID,
    cash_location_id UUID
);
```

### 3. v_journal_lines_readable (View)
거래 내역을 읽기 쉽게 표시하는 뷰
```sql
-- 주요 필드
- entry_date: 거래일
- company_name: 회사명
- store_name: 가게명
- account_name: 계정명
- cash_location_name: 현금 위치
- description: 설명
- debit/credit: 차대변
- full_name: 생성자 이름
- journal_id: 전표 ID
```

## Direct API Calls

### 1. 거래 내역 조회
```javascript
const url = 'https://atkekzwgukdvucqntryo.supabase.co/rest/v1/v_journal_lines_readable';
const headers = {
    'apikey': SUPABASE_ANON_KEY,
    'Authorization': 'Bearer ' + SUPABASE_ANON_KEY
};

// 파라미터 예시
const params = new URLSearchParams({
    'company_id': 'eq.78ebfdae-a630-4638-bfdf-8e0c1e5b9e53',
    'entry_date': 'gte.2025-07-01',
    'order': 'entry_date.desc,created_at.desc',
    'limit': 20
});

fetch(url + '?' + params, { headers })
    .then(response => response.json())
    .then(data => console.log(data));
```

### 2. 전표 생성자별 집계
```sql
-- SQL 쿼리 예시
SELECT 
    je.created_by,
    u.first_name || ' ' || u.last_name as creator_name,
    COUNT(*) as transaction_count
FROM journal_entries je
LEFT JOIN users u ON je.created_by = u.user_id
WHERE je.company_id = :company_id
  AND je.entry_date >= :date_from
GROUP BY je.created_by, u.first_name, u.last_name
ORDER BY transaction_count DESC;
```

## Filter Parameters

### Store Filter
```javascript
// 가게별 필터
if (params.store_id) {
    queryParams.append('store_id', `eq.${params.store_id}`);
}
```

### Date Range Filter
```javascript
// 날짜 범위 필터
queryParams.append('entry_date', `gte.${date_from}`);
queryParams.append('entry_date', `lte.${date_to}`);
```

### Created By Filter
```javascript
// 생성자 필터
queryParams.append('created_by', `eq.${user_id}`);
```

## Important Notes

### 1. 이름 표시 불일치
- **문제**: users 테이블과 view의 이름 순서가 다를 수 있음
- **예시**: 
  - users: first_name="Tom", last_name="Trang"
  - view: full_name="Trang Tom"
- **해결**: user_id로 필터링하되, 표시는 view의 full_name 사용

### 2. Journal Entry 그룹화
거래는 Journal Entry 단위로 그룹화되어 표시:
```javascript
// 전표 헤더
Jul 12, 2025 - photo plastic cover deposit 40%    Trang Tom   Total: ₫3,800,000

// 전표 라인들
    office supplies expenses    ₫3,800,000
    Cash                                      ₫3,800,000    TP bank- (Nhat Chieu)
```

### 3. 현금 위치 표시
- Cash 계정의 경우 cash_location_name 표시
- 아이콘: 🏦 (위치 아이콘)

### 4. 거래처 표시
- Notes Payable/Receivable, Accounts Payable/Receivable의 경우
- counterparty_name 표시
- 아이콘: 🏢 (건물 아이콘)

## Test Data

### Company ID
- Cameroon Photo: `78ebfdae-a630-4638-bfdf-8e0c1e5b9e53`

### User IDs
```javascript
const userIds = {
    'Jin Lee': '0d2e61ad-e230-454e-8b90-efbe1c1a9268',
    'Ngoc Minh': '7e733369-cd3a-4422-b174-dc612fdc08a5',
    'khánh chi': '60901b04-59cd-4c87-944c-66cee0ffa4c4',
    'Nga Nga': '79a4bddb-8c7c-4754-9745-320dfa5b6411',
    'Trang Tom': '9dcaf9c0-c2bb-4e57-89ce-a1b7ca92dc7d',
    'le nhi': '924ff442-8792-4356-8c73-3df367399eff',
    'Ha Tran Thu': '581067ed-698b-4289-a0a5-6599c554308a',
    'Seo seungbin': '50fe3528-df4c-4d14-ac08-12d9db720b18',
    'System': '99999999-9999-9999-9999-999999999999'
};
```

### Store IDs
```javascript
const storeIds = {
    'Cameroon Nha Trang': 'store_id_1',
    'Headsup Nha Trang': 'store_id_2',
    'Cameroon Nhat Chieu': 'store_id_3',
    'Cameroon Chua Boc': 'store_id_4',
    'Headsup Hanoi': 'store_id_5'
};
```
