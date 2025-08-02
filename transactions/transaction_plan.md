# Transaction History 최적화 계획

## 1. 개요

현재 Transaction History 페이지의 데이터 조회 로직을 최적화하여 성능을 향상시키고 코드를 단순화하는 계획입니다.

## 2. 현재 문제점

- 여러 번의 API 호출로 인한 성능 저하
- 복잡한 데이터 매핑 로직
- Store 필터링 시 일부 데이터 누락
- Counterparty 정보 표시 불일치

## 3. 최적의 해결 방안: 하이브리드 접근법

### 3.1 데이터 조회 전략

1. **journal_entries 테이블**: 페이징과 기본 필터링용
2. **v_journal_lines_complete** (새로운 뷰): 모든 상세 정보 조회용

#### 장점:
- journal_entries로 먼저 조회하면 정확한 페이징 가능
- 뷰에서 모든 정보를 가져오면 추가 조회 불필요
- 두 방식의 장점만 결합

### 3.2 API 호출 프로세스

```
1단계: journal_entries 조회
- 페이징 (LIMIT/OFFSET)
- 기본 필터 (날짜, 회사, 작성자)
- journal_id 목록 획득

2단계: v_journal_lines_complete 조회
- WHERE journal_id IN (...)
- 추가 필터 (store_id, account_name)
- 정렬: journal_id, line_id
```

## 4. 새로운 뷰 설계: v_journal_lines_complete

### 4.1 설계 원칙

1. **완전한 정보**: 추가 조회가 필요 없도록 모든 정보 포함
2. **효율적 조인**: 필요한 조인만 수행
3. **Counterparty 우선순위**: COALESCE로 라인 > Journal 순서 처리
4. **NULL 안전성**: LEFT JOIN으로 데이터 누락 방지

### 4.2 포함할 필드

#### 기본 정보
- line_id, journal_id, account_id
- debit, credit
- line_description

#### Journal Entry 정보
- entry_date, journal_description
- journal_created_at, journal_created_by

#### 읽기 쉬운 이름들
- account_name, store_name
- cash_location_name
- counterparty_name (우선순위 적용)
- created_by_name
- company_name

#### 필터링용 ID들
- store_id, company_id
- counterparty_id (최종 선택된 것)

### 4.3 뷰 생성 SQL (예시)

```sql
CREATE OR REPLACE VIEW v_journal_lines_complete AS
SELECT 
    -- Line 정보
    jl.line_id,
    jl.journal_id,
    jl.account_id,
    a.account_name,
    jl.debit,
    jl.credit,
    jl.description as line_description,
    
    -- Journal Entry 정보
    je.entry_date,
    je.description as journal_description,
    je.created_at as journal_created_at,
    je.created_by,
    
    -- Store 정보
    jl.store_id,
    s.store_name,
    
    -- Cash Location 정보
    jl.cash_location_id,
    cl.location_name as cash_location_name,
    
    -- Counterparty 정보 (우선순위 적용)
    COALESCE(jl.counterparty_id, je.counterparty_id) as counterparty_id,
    COALESCE(cp_line.name, cp_journal.name) as counterparty_name,
    
    -- User 정보
    u.full_name as created_by_name,
    
    -- Company 정보
    je.company_id,
    c.company_name

FROM journal_lines jl
JOIN journal_entries je ON jl.journal_id = je.journal_id
JOIN accounts a ON jl.account_id = a.account_id
LEFT JOIN stores s ON jl.store_id = s.store_id
LEFT JOIN cash_locations cl ON jl.cash_location_id = cl.location_id
LEFT JOIN counterparties cp_line ON jl.counterparty_id = cp_line.counterparty_id
LEFT JOIN counterparties cp_journal ON je.counterparty_id = cp_journal.counterparty_id
LEFT JOIN users u ON je.created_by = u.user_id
LEFT JOIN company c ON je.company_id = c.company_id;
```

## 5. 최적화 전략

### 5.1 데이터베이스 레벨

#### 인덱스 전략
- journal_id, company_id에 복합 인덱스
- entry_date에 인덱스
- 자주 조인되는 FK들에 인덱스

#### 뷰 성능
- 초기에는 일반 VIEW로 시작
- 성능 이슈 시 Materialized View 고려
- 필요시 부분 인덱스 활용

### 5.2 애플리케이션 레벨

#### 캐싱 전략
- 자주 변하지 않는 데이터(계정명, 회사명 등) 캐싱
- 세션 레벨에서 메타데이터 캐싱

#### 지연 로딩
- 초기 로드는 20개 제한
- 스크롤 시 추가 로드
- 필터 변경 시 처음부터 다시 로드

## 6. 필터링 전략

### 6.1 서버 사이드 필터
- company_id (필수)
- date_from, date_to
- created_by

### 6.2 하이브리드 필터
- store_id: 뷰에서 필터하되, NULL 값도 포함
- account_name: 뷰에서 필터

### 6.3 클라이언트 사이드
- keyword 검색 (성능상 이유)

## 7. 엣지케이스 처리

### 7.1 Store 필터링
- store_id가 NULL인 라인도 표시
- 회사 전체 거래는 모든 store에서 보임

### 7.2 Counterparty 표시
- 라인별 counterparty 우선
- 없으면 journal entry counterparty
- 둘 다 없으면 빈 값

### 7.3 권한 처리
- 뷰 레벨에서 RLS(Row Level Security) 적용
- company_id 기반 필터링 필수

## 8. 확장성 고려사항

### 8.1 다국어 지원
- 계정명 등은 별도 번역 테이블 고려

### 8.2 감사 추적
- created_by, created_at 정보 완전 보존

### 8.3 성능 모니터링
- 뷰 조회 시간 측정
- 필요시 쿼리 최적화

## 9. 구현 단계

### Phase 1: 분석 및 설계
1. 현재 v_journal_lines_readable 구조 분석
2. 필요한 필드 목록 확정
3. 뷰 설계 문서 작성

### Phase 2: 데이터베이스 작업
1. v_journal_lines_complete 뷰 생성
2. 필요한 인덱스 추가
3. 성능 테스트

### Phase 3: 애플리케이션 수정
1. PHP 코드 리팩토링
2. JavaScript 로직 단순화
3. 에러 처리 강화

### Phase 4: 테스트 및 최적화
1. 단위 테스트
2. 통합 테스트
3. 성능 최적화

### Phase 5: 배포 및 모니터링
1. 단계적 배포
2. 성능 모니터링
3. 사용자 피드백 수집

## 10. 예상 효과

- **성능 향상**: API 호출 횟수 50% 감소
- **코드 단순화**: 매핑 로직 제거로 코드량 30% 감소
- **유지보수성**: 뷰 중심 구조로 변경 용이
- **확장성**: 새로운 필드 추가 시 뷰만 수정

## 11. 리스크 및 대응 방안

### 리스크
1. 뷰 성능 저하 가능성
2. 기존 코드와의 호환성 문제
3. 대용량 데이터 처리 시 메모리 이슈

### 대응 방안
1. 단계적 마이그레이션
2. 롤백 계획 수립
3. 성능 모니터링 강화

## 12. 참고 사항

- 이 계획은 현재 시스템의 안정성을 유지하면서 점진적으로 개선하는 것을 목표로 함
- 각 단계별로 충분한 테스트를 거쳐 안정성 확보
- 필요시 계획 수정 및 보완 가능

## 13. 2025-07-14 테스트 결과 및 추가 이슈

### 13.1 발견된 문제점

#### HQ 필터링 500 에러
- **증상**: HQ (본사) 탭 클릭 시 500 Internal Server Error 발생
- **원인**: API 수정 후 발생, 쿼리 구조나 파라미터 문제로 추정
- **영향**: HQ 레벨에서 모든 기능 사용 불가

#### 계정 필터링 실패
- **증상**: Notes Payable 등 계정 타입 필터 적용 시 거래가 모두 사라짐
- **원인**: 필터링 순서와 로직의 문제
- **영향**: 특정 계정 타입의 거래만 보는 것이 불가능

### 13.2 해야 할 작업

#### 긴급 수정 사항
1. **API 에러 디버깅**
   - error_log 추가하여 구체적인 에러 확인
   - v_journal_lines_complete 뷰 존재 여부 확인
   - SQL 쿼리 직접 실행하여 문제 파악

2. **필터링 로직 재검토**
   ```php
   // 제안하는 새로운 접근 방식
   // 1. Journal Entry 레벨에서 필터링
   // 2. 각 Journal의 모든 라인 검토
   // 3. 조건 충족 시 전체 Journal 포함
   ```

#### 데이터베이스 확인 사항
1. v_journal_lines_complete 뷰 구조 및 데이터 확인
2. Notes Payable 계정의 실제 거래 존재 여부
3. store_id NULL 값 처리 확인

#### 테스트 계획
1. 각 필터 레벨별 기본 동작 테스트
2. 다양한 계정 타입으로 필터 테스트
3. 복합 필터 (스토어 + 계정 + 날짜) 테스트

### 13.3 개선 제안

#### 단기 개선
1. 에러 처리 강화 - 사용자에게 명확한 에러 메시지 제공
2. 로딩 상태 개선 - 실패 시 재시도 옵션 제공
3. 필터 상태 표시 - 현재 적용된 필터 시각화

#### 장기 개선
1. 필터링 성능 최적화
2. 캐싱 전략 도입
3. 필터 프리셋 기능 추가

### 13.4 임시 해결책

현재 사용자가 겪는 문제를 완화하기 위한 임시 조치:
1. HQ 필터 사용 시 에러 메시지와 함께 All Stores 사용 권장
2. 계정 필터 대신 검색 기능 사용 안내
3. 필요시 직접 SQL 쿼리로 데이터 추출 지원
