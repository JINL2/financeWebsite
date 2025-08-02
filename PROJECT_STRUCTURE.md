# 재무관리 시스템 프로젝트 구조

## 📁 프로젝트 전체 구조

```
/Applications/XAMPP/xamppfiles/htdocs/mysite/luxapp/finance/
├── 📄 index.php                    # 메인 페이지 (대시보드로 리다이렉트)
├── 📄 login.php                     # 로그인 페이지
├── 📄 .htaccess                     # Apache 설정
├── 📄 README.md                     # 프로젝트 설명
├── 📄 TEST_GUIDE.md                 # 테스트 가이드 (실제 계정 정보 포함)
├── 📄 supabase.md                   # Supabase 사용 가이드
│
├── 📂 api/                          # API 엔드포인트
│   ├── 📄 get_income_statement.php  # 손익계산서 데이터 API (기존)
│   ├── 📄 income_statement.php      # 손익계산서 API (새버전 - rpc_get_income_statement 사용)
│   └── 📄 test_income_statement.php # 테스트용 손익계산서 API
│
├── 📂 assets/                       # 정적 자원
│   ├── 📂 css/
│   │   └── 📄 style.css            # 공통 스타일
│   └── 📂 js/
│       └── 📄 app.js               # 공통 JavaScript
│
├── 📂 balance-sheet/               # 재무상태표 모듈
│   ├── 📄 index.php                # 재무상태표 페이지
│   └── 📄 api.php                  # 재무상태표 API
│
├── 📂 common/                      # 공통 모듈
│   ├── 📄 auth.php                 # 인증 함수
│   ├── 📄 config.php               # 설정 (Supabase 키 등)
│   ├── 📄 db.php                   # 데이터베이스 연결
│   └── 📄 functions.php            # 공통 함수
│
├── 📂 dashboard/                   # 대시보드 모듈
│   ├── 📄 index.php                # 대시보드 메인 페이지
│   └── 📄 api.php                  # 대시보드 데이터 API
│
├── 📂 docs/                        # 문서
│   └── 📄 system_design.md         # 시스템 설계 문서
│
├── 📂 income-statement/            # 손익계산서 모듈 (기존)
│   ├── 📄 index.php                # 손익계산서 페이지 (기존 버전)
│   ├── 📄 plan_income.md           # 손익계산서 구축 계획서
│   ├── 📄 README.md                # 모듈 설명
│   ├── 📄 RPC_FUNCTIONS_GUIDE.md   # RPC 함수 가이드
│   ├── 📄 IMPLEMENTATION_NOTES.md  # 구현 노트
│   └── 📄 IMPLEMENTATION_STATUS.md # 구현 상태 (2025-01-13 추가)
│
├── 📂 journal-entry/               # 전표 입력 모듈
│   ├── 📄 index.php                # 전표 입력 페이지
│   └── 📄 api.php                  # 전표 입력 API
│
├── 📂 sql/                         # SQL 스크립트 (2025-01-13 추가)
│   └── 📄 rpc_get_income_statement.sql  # 손익계산서 RPC 함수
│
├── 📂 transactions/                # 거래 내역 모듈
│   ├── 📄 index.php                # 거래 내역 조회 페이지
│   └── 📄 api.php                  # 거래 내역 API
│
└── 📄 income_statement.php         # 손익계산서 페이지 (새버전 - 2025-01-13 추가)
```

## 🔑 주요 파일 설명

### 1. 인증 및 공통 모듈 (`/common/`)
- **auth.php**: 사용자 인증 체크, requireAuth() 함수 제공
- **config.php**: Supabase URL 및 API 키 설정
- **db.php**: SupabaseDB 클래스, 데이터베이스 연결 관리
- **functions.php**: 공통 유틸리티 함수들

### 2. 손익계산서 관련 파일

#### 기존 시스템 (`/income-statement/`)
- **index.php**: 기존 손익계산서 페이지
- RPC 함수: `get_income_statement`, `get_income_statement_monthly` 등 사용
- API: `/api/get_income_statement.php` 사용

#### 새 시스템 (루트 디렉토리)
- **income_statement.php**: 새로운 손익계산서 페이지 (2025-01-13 추가)
- RPC 함수: `rpc_get_income_statement` 사용 (계획서 기반)
- API: `/api/income_statement.php` 사용
- 특징: 카테고리별 그룹핑 및 subtotal 지원

### 3. API 구조 (`/api/`)
- **income_statement.php**: 새 손익계산서 API (rpc_get_income_statement 호출)
- **get_income_statement.php**: 기존 손익계산서 API (v_income_statement_by_store 뷰 사용)
- **test_income_statement.php**: 테스트용 더미 데이터 API

## 📊 데이터베이스 구조

### RPC 함수
- `rpc_get_income_statement(period DATE, company_id UUID, store_id UUID)`
  - 손익계산서 데이터를 카테고리별로 분류하여 반환
  - 카테고리: sales_revenue, cogs, operating_expense, tax, comprehensive_income

### 주요 테이블
- `journal_entries`: 전표 헤더
- `journal_lines`: 전표 상세
- `accounts`: 계정과목
- `companies`: 회사 정보
- `stores`: 가게 정보

### 뷰 테이블
- `v_income_statement_by_store`: 가게별 손익 (⚠️ 본사 거래 제외 문제 있음)
- `v_journal_lines_readable`: 거래 내역 상세

## 🚀 새로운 손익계산서 시스템 사용법

### 1. SQL 실행 (Supabase에서)
```sql
-- /sql/rpc_get_income_statement.sql 파일 내용 실행
```

### 2. 페이지 접근
```
http://localhost/luxapp/finance/income_statement.php?user_id=XXX&company_id=YYY
```

### 3. API 테스트
```
http://localhost/luxapp/finance/api/income_statement.php?user_id=XXX&company_id=YYY&year=2025&month=1
```

## 🔧 개발 환경 설정

### 필수 요구사항
- XAMPP (Apache + PHP)
- Supabase 프로젝트
- 프로젝트 ID: `atkekzwgukdvucqntryo`

### 설정 파일 수정
1. `/common/config.php`에서 Supabase API 키 설정
2. 테스트 모드 비활성화 (실제 데이터 사용시)

## 📝 테스트 계정 정보

### Jin Lee 계정 (실제 데이터)
- User ID: `0d2e61ad-e230-454e-8b90-efbe1c1a9268`
- Company: Cameraon&Headsup (`ebd66ba7-fde7-4332-b6b5-0d8a7f615497`)

### 테스트 계정 (더미 데이터)
- User ID: `test-user-1`
- Company ID: `test-company-1`

## ⚠️ 주의사항

1. **경로 문제**: 
   - `/income-statement/` 폴더 내 파일들은 `../common/` 경로 사용
   - 루트의 파일들은 `common/` 경로 사용

2. **API 버전**:
   - 기존: `get_income_statement.php` (뷰 기반)
   - 신규: `income_statement.php` (RPC 함수 기반)

3. **본사 거래 처리**:
   - 기존 뷰는 본사 거래(store_id = NULL) 제외됨
   - 새 RPC 함수는 본사 거래 포함

## 🔄 마이그레이션 가이드

### 기존 시스템에서 새 시스템으로
1. Supabase에 `rpc_get_income_statement` 함수 생성
2. 메뉴 링크를 `/income-statement/`에서 `/income_statement.php`로 변경
3. API 엔드포인트를 새 버전으로 교체

## 📅 업데이트 이력

- **2025-01-13**: 
  - 새 손익계산서 시스템 추가 (`income_statement.php`)
  - RPC 함수 `rpc_get_income_statement` 생성
  - 카테고리별 그룹핑 및 subtotal 기능 구현
  - 프로젝트 구조 문서화

- **2025-01-12**: 
  - 기존 손익계산서 버그 수정
  - RPC 함수 문서 추가

## 🎯 다음 개발자를 위한 팁

1. **새 기능 추가시**: `/common/` 폴더의 공통 함수 활용
2. **API 개발시**: 인증 체크 필수 (`requireAuth()`)
3. **UI 수정시**: Bootstrap 5 및 `/assets/css/style.css` 활용
4. **테스트시**: `TEST_GUIDE.md` 참고하여 실제 데이터로 테스트

---
최종 업데이트: 2025-01-13
