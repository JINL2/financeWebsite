# Transactions 모듈

## 사용하는 Supabase RPC 함수 및 View

### 1. 거래 내역 조회
- **View**: `v_journal_lines_readable`
  - 용도: 거래 내역 상세 조회
  - 컬럼: entry_date, company_name, store_name, account_name, cash_location_name, description, debit, credit, created_at, journal_type, journal_id, line_id, full_name
  - 필터: company_id, store_id, created_by, account_id, date_from, date_to, keyword

### 2. 필터용 데이터
- **RPC**: `get_user_companies_and_stores`
  - 파라미터: p_user_id
  - 용도: 사용자가 접근 가능한 회사/가게 목록

- **RPC**: `get_company_users`
  - 파라미터: p_company_id
  - 용도: 회사의 사용자 목록 (필터용)

- **View**: `accounts`
  - 용도: 계정과목 목록 (필터용)
  - 필터: company_id

## API 엔드포인트

### GET /transactions/api.php?action=get_transactions
파라미터:
- company_id (필수)
- store_id (선택)
- filter_user_id (선택)
- account_id (선택)
- date_from (선택)
- date_to (선택)
- keyword (선택)
- page (선택, 기본값: 1)
- limit (선택, 기본값: 100)

### GET /transactions/api.php?action=get_filters
- 필터에 사용할 가게, 사용자, 계정 목록 반환
