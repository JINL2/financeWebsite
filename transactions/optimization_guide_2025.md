# Transaction History 성능 최적화 가이드 - 수정판

## 📋 문서 정보
- **작성일**: 2025-07-14
- **수정일**: 2025-07-14
- **작성자**: AI Assistant
- **목적**: Transaction History 페이지의 성능 문제 해결
- **핵심**: Journal Entry 구조를 유지하면서 조회 방식 최적화

## 🎯 핵심 원칙: Entry 레벨 필터링은 올바르다

### 회계 시스템의 기본 구조
```
Journal Entry (하나의 완전한 거래)
├── Line 1: 차변 (Debit)
└── Line 2: 대변 (Credit)
```

**중요**: 거래는 반드시 Entry 단위로 표시되어야 함. 일부 라인만 보여주면 차대변이 맞지 않음.

## 🚨 현재 문제점 (정확한 진단)

### 문제의 본질
1. **필터링 자체는 문제없음** - Journal Entry 레벨 필터링은 회계적으로 올바름
2. **진짜 문제**: 필터링 후 각 Entry의 상세 정보를 개별적으로 조회
3. **결과**: Entry 1개당 5-6번의 추가 API 호출 발생

### 현재 흐름 분석
```
1. Journal Entries 조회 (필터 적용) ✅ 올바름
2. 각 Entry별로:
   - Journal Lines 조회 ❌ 비효율
   - Account Names 조회 ❌ 비효율
   - Store Names 조회 ❌ 비효율
   - User Names 조회 ❌ 비효율
   - Counterparty Names 조회 ❌ 비효율
```

## 💡 올바른 해결 방안: N+1 쿼리 문제 해결

### 핵심 전략
필터링 로직은 그대로 유지하되, 조회 방식만 개선

## 📝 구현 가이드

### Phase 1: 즉시 수정 사항 (오늘)

#### 1.1 에러 수정
```php
// api.php - HQ 필터 수정
if ($store_id === 'HQ') {
    // HQ = 본사 레벨 거래 (store_id가 NULL인 라인이 있는 거래)
    // 서브쿼리로 처리
    $store_filter = " AND EXISTS (
        SELECT 1 FROM journal_lines jl 
        WHERE jl.journal_id = je.journal_id 
        AND jl.store_id IS NULL
    )";
} elseif ($store_id && $store_id !== 'all') {
    // 특정 스토어 필터
    $store_filter = " AND EXISTS (
        SELECT 1 FROM journal_lines jl 
        WHERE jl.journal_id = je.journal_id 
        AND jl.store_id = '{$store_id}'
    )";
} else {
    $store_filter = "";
}
```

#### 1.2 계정 필터 수정
```php
// 계정 필터도 서브쿼리로 처리
if (!empty($account_filter)) {
    $account_condition = " AND EXISTS (
        SELECT 1 FROM journal_lines jl 
        JOIN accounts a ON jl.account_id = a.account_id
        WHERE jl.journal_id = je.journal_id 
        AND a.account_name = '{$account_filter}'
    )";
}
```

### Phase 2: 조회 최적화 (이번 주)

#### 2.1 일괄 조회 방식으로 변경

**현재 방식 (비효율적)**
```php
// 각 Entry마다 개별 조회
foreach ($entries as $entry) {
    $lines = getJournalLines($entry['journal_id']);
    $accounts = getAccounts($lines);
    $stores = getStores($lines);
    // ... 반복
}
```

**개선된 방식 (효율적)**
```php
// 1단계: Entry 필터링 (현재 로직 유지)
$query = "SELECT * FROM journal_entries je 
          WHERE company_id = ? {$date_filter} {$store_filter} {$account_filter}
          ORDER BY entry_date DESC LIMIT 20";
$entries = $db->query($query);

// 2단계: Entry ID 수집
$entry_ids = array_column($entries, 'journal_id');
$ids_string = implode(',', $entry_ids);

// 3단계: 모든 상세 정보 한 번에 조회
$details_query = "
    SELECT 
        je.journal_id,
        je.entry_date,
        je.description as journal_description,
        je.created_by,
        u.full_name as created_by_name,
        jl.line_id,
        jl.debit,
        jl.credit,
        jl.description as line_description,
        a.account_name,
        a.account_type,
        s.store_name,
        cl.location_name as cash_location,
        COALESCE(cp_line.name, cp_entry.name) as counterparty_name
    FROM journal_entries je
    JOIN journal_lines jl ON je.journal_id = jl.journal_id
    JOIN accounts a ON jl.account_id = a.account_id
    LEFT JOIN stores s ON jl.store_id = s.store_id
    LEFT JOIN cash_locations cl ON jl.cash_location_id = cl.location_id
    LEFT JOIN counterparties cp_line ON jl.counterparty_id = cp_line.counterparty_id
    LEFT JOIN counterparties cp_entry ON je.counterparty_id = cp_entry.counterparty_id
    LEFT JOIN users u ON je.created_by = u.user_id
    WHERE je.journal_id IN ({$ids_string})
    ORDER BY je.journal_id, jl.line_id";

$all_details = $db->query($details_query);

// 4단계: 데이터 그룹핑
$grouped_data = [];
foreach ($all_details as $row) {
    $journal_id = $row['journal_id'];
    if (!isset($grouped_data[$journal_id])) {
        $grouped_data[$journal_id] = [
            'journal_id' => $journal_id,
            'entry_date' => $row['entry_date'],
            'created_by_name' => $row['created_by_name'],
            'lines' => []
        ];
    }
    $grouped_data[$journal_id]['lines'][] = [
        'account_name' => $row['account_name'],
        'debit' => $row['debit'],
        'credit' => $row['credit'],
        'store_name' => $row['store_name'],
        'counterparty_name' => $row['counterparty_name'],
        // ... 기타 필드
    ];
}
```

#### 2.2 Supabase 특화 최적화
```javascript
// Supabase의 PostgREST 기능 활용
async function loadTransactionsOptimized(filters) {
    // 1단계: 필터된 Entry ID 가져오기
    const { data: entries } = await supabase
        .from('journal_entries')
        .select('journal_id')
        .eq('company_id', filters.company_id)
        .gte('entry_date', filters.date_from)
        .lte('entry_date', filters.date_to)
        .order('entry_date', { ascending: false })
        .limit(20);
    
    const entryIds = entries.map(e => e.journal_id);
    
    // 2단계: 상세 정보 일괄 조회
    const { data: details } = await supabase
        .from('journal_lines')
        .select(`
            *,
            journal_entries!inner(*),
            accounts(*),
            stores(*),
            cash_locations(*),
            counterparties(*)
        `)
        .in('journal_id', entryIds);
    
    // 3단계: 클라이언트에서 그룹핑
    return groupByJournalEntry(details);
}
```

### Phase 3: 추가 최적화 (선택사항)

#### 3.1 데이터베이스 인덱스 추가
```sql
-- 필터링 성능 향상을 위한 인덱스
CREATE INDEX idx_journal_entries_filter 
    ON journal_entries(company_id, entry_date DESC);

CREATE INDEX idx_journal_lines_journal 
    ON journal_lines(journal_id);

-- 서브쿼리 성능 향상
CREATE INDEX idx_journal_lines_store 
    ON journal_lines(journal_id, store_id);
```

#### 3.2 캐싱 전략 (선택사항)
```php
// 자주 사용되는 메타데이터 캐싱
class MetadataCache {
    private static $accounts = null;
    private static $stores = null;
    
    public static function getAccounts() {
        if (self::$accounts === null) {
            self::$accounts = // DB에서 조회
        }
        return self::$accounts;
    }
}
```

## 🚀 구현 우선순위

### 즉시 (오늘)
1. ✅ HQ 필터 에러 수정 (서브쿼리 방식)
2. ✅ 계정 필터 로직 수정
3. ✅ 디버깅 로그 추가

### 단기 (3일 내)
1. ✅ 일괄 조회 로직 구현
2. ✅ 데이터 그룹핑 함수 작성
3. ✅ 프론트엔드 수정 (단순화)

### 중기 (1주일 내)
1. ✅ 인덱스 최적화
2. ✅ 페이징 개선
3. ✅ 에러 처리 강화

## 📊 예상 효과

### 성능 개선
- **API 호출**: Entry당 5-6회 → 전체 2회 (90% 감소)
- **응답 시간**: 3초 → 0.5초 (83% 개선)
- **코드 복잡도**: 크게 감소

### 유지한 것들
- ✅ Journal Entry 구조 (회계 원칙)
- ✅ 기존 테이블 구조
- ✅ 필터링 로직의 정확성

## ⚠️ 주의사항

### 회계 원칙 준수
1. **Entry 단위 유지**: 거래를 쪼개서 표시하지 않음
2. **차대변 균형**: 항상 전체 Entry를 표시
3. **필터 의미**: "관련된 전체 거래"를 보여줌

### 기술적 제약
1. **Supabase RLS 비활성화**: API 레벨에서 권한 체크
2. **기존 테이블 수정 금지**: 조회 방식만 개선
3. **ID 목록 크기**: 매우 많은 Entry 조회 시 IN 절 제한 고려

## 🔍 테스트 체크리스트

### 기능 검증
- [ ] HQ 필터: 본사 거래만 정확히 표시
- [ ] Store 필터: 해당 스토어 관련 전체 거래 표시
- [ ] Account 필터: 해당 계정 포함된 전체 거래 표시
- [ ] 복합 필터: 여러 필터 동시 적용 시 정상 작동

### 성능 검증
- [ ] 20개 거래 로드 시간 < 1초
- [ ] API 호출 횟수 확인
- [ ] 대량 데이터에서도 안정적

### 데이터 정확성
- [ ] 모든 Entry의 차대변 균형 표시
- [ ] 필터링 후에도 완전한 거래 정보 유지
- [ ] Counterparty 우선순위 정확히 적용

## 💭 핵심 교훈

> "문제를 정확히 진단하는 것이 해결의 90%다"

- 필터링 로직 자체는 올바랐음
- N+1 쿼리 문제가 진짜 원인
- 회계 원칙을 지키면서도 성능 개선 가능

## 🎉 구현 결과 (2025-07-14 업데이트)

### ✅ 테스트 완료
1. **PostgREST API 최적화**: 3회 API 호출로 감소
2. **Supabase RPC 함수**: 1회 API 호출로 최적화
3. **성능 개선**: 19.5초 → 0.3초 (98.5% 개선)

### 📦 생성된 RPC 함수
- `get_transactions_optimized`: 기본 버전
- `get_transactions_optimized_v2`: 전체 기능 버전
- `test_transaction_filters`: 테스트용

자세한 내용은 [RPC_IMPLEMENTATION_RESULTS.md](./RPC_IMPLEMENTATION_RESULTS.md) 참조

---

**마지막 업데이트**: 2025-07-14 (성공적 구현 및 테스트 완료)
**다음 검토일**: 2025-07-21
**변경사항**: RPC 함수 구현으로 서버사이드 필터링 완성