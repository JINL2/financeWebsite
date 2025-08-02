# Dashboard 모듈

## 사용하는 Supabase RPC 함수 및 View

### 1. 요약 데이터 조회
- **View**: `v_store_balance_summary`
  - 용도: 자산, 부채, 자본 요약
  - 컬럼: company_id, store_id, store_name, total_debit, total_credit, balance_difference

- **View**: `v_store_income_summary`
  - 용도: 수익, 비용 요약
  - 컬럼: company_id, store_id, store_name, total_income, total_expense, net_income

### 2. 최근 거래 내역
- **View**: `v_journal_lines_readable`
  - 용도: 최근 거래 10건 표시
  - 필터: ORDER BY entry_date DESC, created_at DESC LIMIT 10

### 3. 현금 잔액 현황
- **View**: `cash_locations_with_total_amount`
  - 용도: 현금 위치별 잔액 표시
  - 컬럼: cash_location_id, location_name, location_type, total_journal_cash_amount

## API 엔드포인트

### GET /dashboard/api.php?action=get_summary
- 자산, 부채, 자본, 수익, 비용 요약 데이터 반환

### GET /dashboard/api.php?action=get_recent_transactions
- 최근 거래 10건 반환

### GET /dashboard/api.php?action=get_cash_balance
- 현금 위치별 잔액 현황 반환
