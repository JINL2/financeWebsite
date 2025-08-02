<?php
// 동적으로 거래를 생성한 사용자 목록을 가져오는 함수
function getTransactionCreators($company_id, $db) {
    try {
        // 최근 3개월간 거래를 생성한 사용자 목록
        $query = "
            SELECT DISTINCT 
                je.created_by as user_id,
                COALESCE(u.first_name || ' ' || u.last_name, 'Unknown User') as full_name,
                COUNT(DISTINCT je.journal_id) as transaction_count
            FROM journal_entries je
            LEFT JOIN users u ON je.created_by = u.user_id
            WHERE je.company_id = :company_id
              AND je.entry_date >= CURRENT_DATE - INTERVAL '3 months'
              AND je.created_by IS NOT NULL
            GROUP BY je.created_by, u.first_name, u.last_name
            ORDER BY transaction_count DESC, full_name
        ";
        
        $result = $db->callRPC('execute_sql', [
            'query' => $query,
            'params' => ['company_id' => $company_id]
        ]);
        
        if (!empty($result)) {
            return array_map(function($row) {
                return [
                    'id' => $row['user_id'],
                    'name' => $row['full_name'],
                    'count' => $row['transaction_count']
                ];
            }, $result);
        }
    } catch (Exception $e) {
        error_log('Error fetching transaction creators: ' . $e->getMessage());
    }
    
    // 실패시 기본 목록 반환
    return [
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
}
?>
