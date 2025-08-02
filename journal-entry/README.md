# Journal Entry 모듈

## 사용하는 Supabase RPC 함수 및 View

### 1. 전표 입력 (핵심!)
- **RPC**: `insert_journal_with_everything`
  - 파라미터:
    - p_base_amount (numeric) - 기본 금액
    - p_company_id (uuid) - 회사 ID
    - p_created_by (uuid) - 작성자 ID
    - p_description (text) - 설명
    - p_entry_date (timestamp) - 거래일시
    - p_lines (jsonb) - 전표 라인 (아래 구조 참조)
    - p_counterparty_id (text) - 거래처 ID (선택)
    - p_if_cash_location_id (text) - 현금위치 ID (선택)
    - p_store_id (text) - 가게 ID (선택)

### p_lines JSONB 구조
```json
[
  {
    "account_name": "계정명",
    "debit": 차변금액,
    "credit": 대변금액,
    "description": "라인설명" (선택),
    "store_id": "가게ID" (선택),
    "cash": {
      "cash_location_id": "UUID"
    },
    "debt": {
      "counterparty_id": "UUID",
      "direction": "receivable" | "payable",
      "category": "accounts" | "note" | "salary" | "dividend",
      "interest_rate": 0.05,
      "due_date": "2024-12-31",
      "linkedCounterparty_store_id": "UUID"
    },
    "fix_asset": {
      "asset_name": "자산명",
      "acquisition_date": "2024-01-01",
      "useful_life_years": 5,
      "salvage_value": 0
    }
  }
]
```

### 2. 데이터 조회용
- **View**: `accounts`
  - 용도: 계정과목 목록
  - 필터: company_id

- **View**: `cash_locations`
  - 용도: 현금 위치 목록
  - 필터: company_id, store_id

- **View**: `counterparties`
  - 용도: 거래처 목록
  - 필터: company_id

- **RPC**: `get_account_mapping`
  - 파라미터: p_counterparty_id, p_company_id
  - 용도: 거래처별 계정 매핑 조회

## API 엔드포인트

### POST /journal-entry/api.php?action=save_entry
전표 저장 (insert_journal_with_everything 호출)

### GET /journal-entry/api.php?action=get_accounts
계정과목 목록 조회

### GET /journal-entry/api.php?action=get_cash_locations
현금 위치 목록 조회

### GET /journal-entry/api.php?action=get_counterparties
거래처 목록 조회

### GET /journal-entry/api.php?action=get_account_mapping
거래처별 계정 매핑 조회
