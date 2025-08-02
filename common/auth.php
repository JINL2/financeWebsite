<?php
/**
 * 인증 및 권한 관리 함수 (환경변수 기반)
 */
require_once 'config.php';
require_once 'db.php';

/**
 * 사용자 권한 확인
 */
function checkAuth($user_id, $company_id, $store_id = null) {
    // 테스트용으로 환경변수에서 허용된 사용자/회사 확인
    $allowed_users = explode(',', getenv('ALLOWED_TEST_USERS') ?: '');
    $allowed_companies = explode(',', getenv('ALLOWED_TEST_COMPANIES') ?: '');
    
    if (in_array($user_id, $allowed_users) && in_array($company_id, $allowed_companies)) {
        return true;
    }
    
    if (!$user_id || !$company_id) {
        return false;
    }
    
    // 테스트 모드: test-user로 시작하는 사용자는 모든 권한 허용
    if (strpos($user_id, 'test-user-') === 0 && strpos($company_id, 'test-company-') === 0) {
        return true;
    }
    
    try {
        $db = new SupabaseDB();
        
        // get_user_companies_and_stores RPC 호출
        $result = $db->callRPC('get_user_companies_and_stores', [
            'p_user_id' => $user_id
        ]);
        
        // RPC 결과가 단일 객체인 경우
        if (isset($result['companies'])) {
            // 새로운 형식: {user_id, companies: [{company_id, stores, ...}]}
            foreach ($result['companies'] as $company) {
                if ($company['company_id'] === $company_id) {
                    // 회사 접근 권한이 있음
                    if ($store_id === null) {
                        return true;
                    }
                    // 특정 가게 확인
                    foreach ($company['stores'] as $store) {
                        if ($store['store_id'] === $store_id) {
                            return true;
                        }
                    }
                }
            }
        } else {
            // 기존 형식: [{company_id, store_id, ...}, ...]
            foreach ($result as $access) {
                if ($access['company_id'] === $company_id) {
                    // 회사 레벨 접근 권한이 있거나
                    if ($store_id === null || $access['store_id'] === null) {
                        return true;
                    }
                    // 특정 가게 접근 권한이 있는 경우
                    if ($access['store_id'] === $store_id) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    } catch (Exception $e) {
        error_log('Auth check failed: ' . $e->getMessage());
        // 테스트 모드 폴백
        if (strpos($user_id, 'test-user-') === 0 && strpos($company_id, 'test-company-') === 0) {
            return true;
        }
        return false;
    }
}

/**
 * 현재 사용자 정보 가져오기
 */
function getCurrentUser($user_id) {
    if (!$user_id) {
        return null;
    }
    
    // 테스트 사용자 데이터
    $testUsers = [
        'test-user-1' => ['user_id' => 'test-user-1', 'full_name' => '홍길동', 'email' => 'hong@example.com'],
        'test-user-2' => ['user_id' => 'test-user-2', 'full_name' => '김철수', 'email' => 'kim@example.com'],
        'test-user-3' => ['user_id' => 'test-user-3', 'full_name' => '이영희', 'email' => 'lee@example.com']
    ];
    
    if (isset($testUsers[$user_id])) {
        return $testUsers[$user_id];
    }
    
    try {
        $db = new SupabaseDB();
        return $db->getOne('users', ['user_id' => $user_id]);
    } catch (Exception $e) {
        error_log('Failed to get user info: ' . $e->getMessage());
        return null;
    }
}

/**
 * 사용자가 접근 가능한 회사 목록
 */
function getUserCompanies($user_id) {
    if (!$user_id) {
        return [];
    }
    
    // 테스트 회사 데이터
    if (strpos($user_id, 'test-user-') === 0) {
        return [
            [
                'company_id' => 'test-company-1',
                'company_name' => 'ABC 회사',
                'stores' => [
                    ['store_id' => 'test-store-1', 'store_name' => '강남점'],
                    ['store_id' => 'test-store-2', 'store_name' => '서초점']
                ]
            ],
            [
                'company_id' => 'test-company-2',
                'company_name' => 'XYZ 상사',
                'stores' => [
                    ['store_id' => 'test-store-3', 'store_name' => '본점']
                ]
            ],
            [
                'company_id' => 'test-company-3',
                'company_name' => '테스트 기업',
                'stores' => []
            ]
        ];
    }
    
    // 환경변수에서 허용된 사용자 확인
    $allowed_users = explode(',', getenv('ALLOWED_TEST_USERS') ?: '');
    if (in_array($user_id, $allowed_users)) {
        // 환경변수에서 회사 정보 가져오기
        $company_data = json_decode(getenv('TEST_COMPANY_DATA') ?: '[]', true);
        if (!empty($company_data)) {
            return $company_data;
        }
    }
    
    try {
        $db = new SupabaseDB();
        $result = $db->callRPC('get_user_companies_and_stores', [
            'p_user_id' => $user_id
        ]);
        
        // RPC 결과가 단일 객체인 경우
        if (isset($result['companies'])) {
            $companies = [];
            foreach ($result['companies'] as $company) {
                $companyData = [
                    'company_id' => $company['company_id'],
                    'company_name' => $company['company_name'],
                    'stores' => []
                ];
                
                if (isset($company['stores']) && is_array($company['stores'])) {
                    foreach ($company['stores'] as $store) {
                        $companyData['stores'][] = [
                            'store_id' => $store['store_id'],
                            'store_name' => $store['store_name']
                        ];
                    }
                }
                
                $companies[] = $companyData;
            }
            return $companies;
        } else {
            // 기존 형식 처리
            $companies = [];
            foreach ($result as $access) {
                if (!isset($companies[$access['company_id']])) {
                    $companies[$access['company_id']] = [
                        'company_id' => $access['company_id'],
                        'company_name' => $access['company_name'],
                        'stores' => []
                    ];
                }
                
                if ($access['store_id']) {
                    $companies[$access['company_id']]['stores'][] = [
                        'store_id' => $access['store_id'],
                        'store_name' => $access['store_name']
                    ];
                }
            }
            
            return array_values($companies);
        }
    } catch (Exception $e) {
        error_log('Failed to get user companies: ' . $e->getMessage());
        return [];
    }
}

/**
 * 사용자가 접근 가능한 가게 목록
 */
function getUserStores($user_id, $company_id) {
    if (!$user_id || !$company_id) {
        return [];
    }
    
    // 환경변수 기반 테스트 데이터 확인
    $allowed_users = explode(',', getenv('ALLOWED_TEST_USERS') ?: '');
    $allowed_companies = explode(',', getenv('ALLOWED_TEST_COMPANIES') ?: '');
    
    if (in_array($user_id, $allowed_users) && in_array($company_id, $allowed_companies)) {
        try {
            $db = new SupabaseDB();
            $stores = $db->query('stores', [
                'company_id' => 'eq.' . $company_id,
                'is_deleted' => 'eq.false',
                'select' => 'store_id,store_name,store_code',
                'order' => 'store_name.asc'
            ]);
            return $stores ?: [];
        } catch (Exception $e) {
            error_log("Get user stores error: " . $e->getMessage());
            return [];
        }
    }
    
    // 기존 방식 (RPC 호출)
    $companies = getUserCompanies($user_id);
    foreach ($companies as $company) {
        if ($company['company_id'] === $company_id) {
            return $company['stores'];
        }
    }
    
    return [];
}

/**
 * 인증 필요 페이지 보호
 */
function requireAuth() {
    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'] ?? null;
    $company_id = $_GET['company_id'] ?? $_SESSION['company_id'] ?? null;
    
    // 환경변수 기반 테스트 허용
    $allowed_users = explode(',', getenv('ALLOWED_TEST_USERS') ?: '');
    $allowed_companies = explode(',', getenv('ALLOWED_TEST_COMPANIES') ?: '');
    
    if (in_array($user_id, $allowed_users) && in_array($company_id, $allowed_companies)) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['company_id'] = $company_id;
        return [
            'user_id' => $user_id,
            'company_id' => $company_id
        ];
    }
    
    if (!$user_id || !$company_id) {
        header('Location: login.php?error=auth_required');
        exit;
    }
    
    if (!checkAuth($user_id, $company_id)) {
        header('Location: login.php?error=access_denied');
        exit;
    }
    
    // 세션에 저장
    $_SESSION['user_id'] = $user_id;
    $_SESSION['company_id'] = $company_id;
    
    return [
        'user_id' => $user_id,
        'company_id' => $company_id
    ];
}
