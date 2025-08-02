# Transactions API Reference

## Overview
Transactions 모듈은 재무 거래 내역을 조회하고 관리하는 기능을 제공합니다.

## API Endpoints

### 1. GET /transactions/api.php?action=get_filters
거래 필터링을 위한 옵션 목록을 가져옵니다.

**Parameters:**
- `user_id` (required): 사용자 ID
- `company_id` (required): 회사 ID
- `store_id` (optional): 가게 ID

**Response:**
```json
{
  "success": true,
  "data": {
    "accounts": [
      {
        "account_id": "uuid",
        "account_name": "Cash",
        "account_type": "asset"
      }
    ],
    "users": [
      {
        "user_id": "uuid",
        "full_name": "Trang Tom"
      }
    ]
  }
}
```

### 2. GET /transactions/api.php?action=get_transactions
거래 내역을 조회합니다. (최적화된 하이브리드 접근법 사용)

**Parameters:**
- `user_id` (required): 사용자 ID
- `company_id` (required): 회사 ID
- `store_id` (optional): 가게 ID
- `filter_user_id` (optional): 생성자 필터
- `account_id` (optional): 계정 ID (deprecated - use account_name)
- `account_name` (optional): 계정명으로 필터
- `date_from` (optional): 시작 날짜 (기본값: 당월 1일)
- `date_to` (optional): 종료 날짜 (기본값: 오늘)
- `keyword` (optional): 검색어
- `page` (optional): 페이지 번호 (기본값: 1)
- `limit` (optional): 페이지당 항목 수 (기본값: 50)

**처리 방식:**
1. journal_entries 테이블에서 페이징과 기본 필터링
2. v_journal_lines_complete 뷰에서 상세 정보 조회
3. store_id 필터 시 NULL 값도 포함 (회사 전체 거래)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "journal_id": "uuid",
      "entry_date": "2025-07-12",
      "description": "거래 설명",
      "account_name": "Cash",
      "debit": "100000.00",
      "credit": "0.00",
      "store_name": "Cameroon Chua Boc",
      "full_name": "Trang Tom"
    }
  ],
  "pagination": {
    "total": 100,
    "page": 1,
    "limit": 50,
    "totalPages": 2
  }
}
```

### 3. GET /transactions/api.php?action=get_journal_details
특정 전표의 상세 정보를 조회합니다.

**Parameters:**
- `user_id` (required): 사용자 ID
- `company_id` (required): 회사 ID
- `journal_id` (required): 전표 ID

**Response:**
```json
{
  "success": true,
  "data": {
    "journal": {
      "journal_id": "uuid",
      "entry_date": "2025-07-12",
      "description": "거래 설명",
      "counterparty_id": "uuid"
    },
    "lines": [
      {
        "line_id": "uuid",
        "account_name": "Cash",
        "debit": "100000.00",
        "credit": "0.00"
      }
    ]
  }
}
```

## Database Views

### v_journal_lines_complete (새로운 최적화 뷰)
모든 거래 정보를 포함하는 완전한 뷰 - 추가 조회 없이 모든 정보 제공

**Columns:**
- `line_id`: 라인 ID
- `journal_id`: 전표 ID
- `account_id`: 계정 ID
- `account_name`: 계정명
- `debit`: 차변
- `credit`: 대변
- `line_description`: 라인 설명
- `entry_date`: 거래일
- `journal_description`: 전표 설명
- `journal_created_at`: 전표 생성일시
- `created_by`: 생성자 ID
- `store_id`: 가게 ID
- `store_name`: 가게명
- `cash_location_id`: 현금 위치 ID
- `cash_location_name`: 현금 위치명
- `counterparty_id`: 거래처 ID (라인 > 전표 우선순위)
- `counterparty_name`: 거래처명 (라인 > 전표 우선순위)
- `created_by_name`: 생성자 이름
- `company_id`: 회사 ID
- `company_name`: 회사명

**특징:**
- COALESCE로 counterparty 우선순위 처리 (라인 레벨 > 전표 레벨)
- LEFT JOIN으로 NULL 안전성 보장
- 모든 필요한 정보를 한 번에 제공하여 추가 조회 불필요

### v_journal_lines_readable (기존 뷰)
거래 내역을 읽기 쉬운 형태로 표시하는 뷰

**Columns:**
- `journal_id`: 전표 ID
- `line_id`: 라인 ID
- `entry_date`: 거래일
- `company_name`: 회사명
- `store_name`: 가게명
- `account_name`: 계정명
- `cash_location_name`: 현금 위치
- `description`: 설명
- `debit`: 차변
- `credit`: 대변
- `created_at`: 생성일시
- `journal_type`: 전표 유형
- `full_name`: 생성자 이름

## JavaScript Functions

### loadTransactions(append = false)
거래 내역을 로드합니다.

```javascript
async function loadTransactions(append = false) {
  // append: true면 기존 목록에 추가, false면 새로 로드
}
```

### filterByStore(storeId)
가게별로 거래를 필터링합니다.

```javascript
function filterByStore(storeId) {
  // storeId: 가게 ID 또는 null (전체)
}
```

### applyFilters()
현재 설정된 필터를 적용합니다.

```javascript
function applyFilters() {
  // 폼 제출하여 필터 적용
}
```

## User IDs (실제 데이터)

```php
$creators = [
    ['id' => '0d2e61ad-e230-454e-8b90-efbe1c1a9268', 'name' => 'Jin Lee'],
    ['id' => '7e733369-cd3a-4422-b174-dc612fdc08a5', 'name' => 'Ngoc Minh'],
    ['id' => '60901b04-59cd-4c87-944c-66cee0ffa4c4', 'name' => 'khánh chi'],
    ['id' => '79a4bddb-8c7c-4754-9745-320dfa5b6411', 'name' => 'Nga Nga'],
    ['id' => '9dcaf9c0-c2bb-4e57-89ce-a1b7ca92dc7d', 'name' => 'Trang Tom'],
    ['id' => '924ff442-8792-4356-8c73-3df367399eff', 'name' => 'le nhi'],
    ['id' => '581067ed-698b-4289-a0a5-6599c554308a', 'name' => 'Ha Tran Thu'],
    ['id' => '50fe3528-df4c-4d14-ac08-12d9db720b18', 'name' => 'Seo seungbin'],
    ['id' => '99999999-9999-9999-9999-999999999999', 'name' => 'Automation System']
];
```

## 최적화 전략

### 1. 하이브리드 접근법
- **1단계**: journal_entries에서 페이징 처리
  - 정확한 페이지네이션
  - 기본 필터 적용 (날짜, 회사, 작성자)
- **2단계**: v_journal_lines_complete에서 상세 정보
  - WHERE journal_id IN (...)
  - 추가 필터 적용 (store_id, account_name)
  - 모든 필요한 정보 한번에 조회

### 2. 성능 향상
- API 호출 횟수 50% 감소
- 복잡한 매핑 로직 제거
- 코드량 30% 감소

### 3. 엣지케이스 처리
- **Store 필터링**: NULL store_id도 표시
- **Counterparty 표시**: 라인 > 전표 우선순위
- **권한 처리**: company_id 기반 필터링 필수

## 주의사항

1. **이름 표시 방식**
   - users 테이블: first_name + last_name
   - v_journal_lines_readable: full_name (순서가 다를 수 있음)
   - 예: Tom Trang → Trang Tom

2. **날짜 필터**
   - 기본값은 당월 데이터
   - date_from과 date_to로 범위 지정 가능

3. **페이지네이션**
   - Load More 방식 사용
   - 한 번에 20개씩 로드

4. **권한 확인**
   - 모든 API 호출시 user_id와 company_id 필수
   - 사용자가 접근 권한이 있는 회사/가게만 조회 가능
